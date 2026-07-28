param(
    [string]$ProjectPath = "",
    [switch]$Fresh,
    [switch]$SkipTests,
    [switch]$Serve
)

$ErrorActionPreference = "Stop"
Set-StrictMode -Version Latest

if ([string]::IsNullOrWhiteSpace($ProjectPath)) {
    $ProjectPath = Split-Path -Parent $PSScriptRoot
}

$ProjectPath = (Resolve-Path -LiteralPath $ProjectPath).Path
Set-Location -LiteralPath $ProjectPath
$env:Path = "C:\xampp\php;C:\xampp\mysql\bin;$env:Path"

function Invoke-Checked {
    param(
        [Parameter(Mandatory)] [string]$Name,
        [Parameter(Mandatory)] [scriptblock]$Command
    )

    Write-Host "`n=== $Name ===" -ForegroundColor Cyan
    & $Command
    $exitCode = $LASTEXITCODE

    if ($exitCode -ne 0) {
        throw "$Name failed with exit code $exitCode."
    }
}


function Set-EnvValue {
    param(
        [Parameter(Mandatory)]
        [string]$Name,

        [Parameter(Mandatory)]
        [AllowEmptyString()]
        [string]$Value
    )

    $envPath = Join-Path (Get-Location) '.env'
    $content = Get-Content -LiteralPath $envPath -Raw
    $escapedName = [regex]::Escape($Name)
    $line = "$Name=$Value"

    if ($content -match "(?m)^$escapedName=.*$") {
        $content = [regex]::Replace($content, "(?m)^$escapedName=.*$", $line)
    } else {
        $content = $content.TrimEnd() + "`r`n$line`r`n"
    }

    [System.IO.File]::WriteAllText($envPath, $content, [System.Text.UTF8Encoding]::new($false))
}

function Require-Command {
    param([Parameter(Mandatory)] [string]$Name)

    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "$Name is not available in this PowerShell session."
    }
}

Write-Host "ClassBridge local setup and repair" -ForegroundColor Green
Write-Host "Project: $ProjectPath"

foreach ($file in @('artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', '.env.example')) {
    if (-not (Test-Path -LiteralPath $file -PathType Leaf)) {
        throw "Required project file is missing: $file"
    }
}

foreach ($command in @('php', 'composer', 'node', 'npm', 'mysql')) {
    Require-Command $command
}

if (-not (Test-NetConnection 127.0.0.1 -Port 3306 -InformationLevel Quiet)) {
    throw "MySQL is not running on 127.0.0.1:3306. Start MySQL from the XAMPP Control Panel and run this script again."
}

Invoke-Checked "PHP VERSION" { php --version }
Invoke-Checked "COMPOSER VERSION" { composer --version }
Invoke-Checked "NODE VERSION" { node --version }
Invoke-Checked "NPM VERSION" { npm --version }

if (-not (Test-Path -LiteralPath '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env'
    Write-Host "Created .env from .env.example." -ForegroundColor Green
} else {
    Write-Host "Preserving the existing local .env file and application key." -ForegroundColor Green
}

Set-EnvValue -Name 'APP_NAME' -Value '"ClassBridge"'
Set-EnvValue -Name 'APP_ENV' -Value 'local'
Set-EnvValue -Name 'APP_DEBUG' -Value 'true'
Set-EnvValue -Name 'APP_URL' -Value 'http://127.0.0.1:8000'
Set-EnvValue -Name 'DB_CONNECTION' -Value 'mysql'
Set-EnvValue -Name 'DB_HOST' -Value '127.0.0.1'
Set-EnvValue -Name 'DB_PORT' -Value '3306'
Set-EnvValue -Name 'DB_DATABASE' -Value 'classbridge'
Set-EnvValue -Name 'DB_USERNAME' -Value 'root'
Set-EnvValue -Name 'DB_PASSWORD' -Value ''
Set-EnvValue -Name 'GOOGLE_AUTH_ENABLED' -Value 'false'

Invoke-Checked "INSTALL PHP DEPENDENCIES" {
    composer install --no-interaction --prefer-dist --optimize-autoloader
}

if (-not (Select-String -LiteralPath '.env' -Pattern '^APP_KEY=base64:.+$' -Quiet)) {
    Invoke-Checked "GENERATE APPLICATION KEY" { php artisan key:generate }
}

Invoke-Checked "CLEAR STALE LARAVEL CACHES" { php artisan optimize:clear }

Invoke-Checked "CREATE DATABASE" {
    mysql -h 127.0.0.1 -P 3306 -u root -e "CREATE DATABASE IF NOT EXISTS classbridge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
}

$tableCountText = (& mysql -h 127.0.0.1 -P 3306 -u root --batch --skip-column-names -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'classbridge';").Trim()
if ($LASTEXITCODE -ne 0) {
    throw "Could not inspect the ClassBridge database."
}

$usersTableText = (& mysql -h 127.0.0.1 -P 3306 -u root --batch --skip-column-names -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'classbridge' AND table_name = 'users';").Trim()
if ($LASTEXITCODE -ne 0) {
    throw "Could not inspect the users table."
}

$tableCount = [int]$tableCountText
$usersTableCount = [int]$usersTableText

if ($Fresh -or $tableCount -eq 0 -or $usersTableCount -eq 0) {
    if ($tableCount -gt 0 -and $usersTableCount -eq 0) {
        Write-Host "The database is incomplete (the users table is missing). Rebuilding the local demo database." -ForegroundColor Yellow
    }

    Invoke-Checked "BUILD DATABASE AND DEMO DATA" {
        php artisan migrate:fresh --seed --force
    }
} else {
    Invoke-Checked "RUN PENDING MIGRATIONS" {
        php artisan migrate --force
    }

    Invoke-Checked "REFRESH IDEMPOTENT DEMO DATA" {
        php artisan db:seed --force
    }
}

if (Test-Path -LiteralPath 'public\hot') {
    Remove-Item -LiteralPath 'public\hot' -Force
}

if (-not (Test-Path -LiteralPath 'public\storage')) {
    php artisan storage:link
    if (($LASTEXITCODE -ne 0) -and (-not (Test-Path -LiteralPath 'public\storage'))) {
        cmd /c mklink /J "public\storage" "storage\app\public"
        if ($LASTEXITCODE -ne 0) {
            throw "Could not create the public storage link."
        }
    }
}

Invoke-Checked "INSTALL LOCKED FRONTEND DEPENDENCIES" { npm ci }
Invoke-Checked "AUDIT FRONTEND DEPENDENCIES" { npm audit --audit-level=moderate }
Invoke-Checked "BUILD CURRENT FRONTEND ASSETS" { npm run build }

if (-not $SkipTests) {
    Invoke-Checked "RUN AUTOMATED TESTS" { php artisan test --stop-on-failure }
    Invoke-Checked "VERIFY PHP CODE STYLE" { php vendor/bin/pint --test }
}

Invoke-Checked "FINAL CACHE CLEAR" { php artisan optimize:clear }

Write-Host "`n==================================================" -ForegroundColor Green
Write-Host "CLASSBRIDGE SETUP COMPLETED SUCCESSFULLY" -ForegroundColor Green
Write-Host "Teacher: teacher@classbridge.test / Demo1234!" -ForegroundColor Green
Write-Host "Student: student@classbridge.test / Demo1234!" -ForegroundColor Green
Write-Host "==================================================" -ForegroundColor Green

if ($Serve) {
    Write-Host "Starting http://127.0.0.1:8000" -ForegroundColor Green
    php artisan serve --host=127.0.0.1 --port=8000 --no-reload
}
