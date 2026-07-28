param(
    [string]$ProjectPath = ""
)

$ErrorActionPreference = "Stop"
Set-StrictMode -Version Latest

if ([string]::IsNullOrWhiteSpace($ProjectPath)) {
    $ProjectPath = Split-Path -Parent $PSScriptRoot
}

Set-Location -LiteralPath (Resolve-Path -LiteralPath $ProjectPath).Path
$env:Path = "C:\xampp\php;C:\xampp\mysql\bin;$env:Path"

function Invoke-NativeCommand {
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

foreach ($requiredFile in @('composer.json', 'package.json', '.env.example')) {
    if (-not (Test-Path $requiredFile)) {
        throw "$requiredFile is missing."
    }
}

Invoke-NativeCommand "PHP VERSION" { php --version }
Invoke-NativeCommand "COMPOSER VERSION" { composer --version }
Invoke-NativeCommand "NODE VERSION" { node --version }
Invoke-NativeCommand "NPM VERSION" { npm --version }

if (-not (Test-Path composer.lock)) {
    Invoke-NativeCommand "GENERATE COMPOSER LOCK" {
        composer update --with-all-dependencies --no-interaction --prefer-dist
    }
}

Invoke-NativeCommand "COMPOSER VALIDATION" { composer validate --strict }
Invoke-NativeCommand "INSTALL PHP DEPENDENCIES" {
    composer install --no-interaction --prefer-dist --optimize-autoloader
}
Invoke-NativeCommand "PHP PLATFORM REQUIREMENTS" { composer check-platform-reqs }
Invoke-NativeCommand "COMPOSER SECURITY AUDIT" { composer audit --locked --abandoned=report }

if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
}

if (-not (Select-String -Path .env -Pattern '^APP_KEY=base64:.+$' -Quiet)) {
    Invoke-NativeCommand "GENERATE APPLICATION KEY" { php artisan key:generate }
}

Invoke-NativeCommand "CLEAR LARAVEL CACHES" { php artisan optimize:clear }
Invoke-NativeCommand "LARAVEL BOOT" { php artisan about }
Invoke-NativeCommand "ROUTE REGISTRATION" { php artisan route:list }

if (-not (Test-Path package-lock.json)) { npm install }
if ($LASTEXITCODE -ne 0) { throw "npm install failed with exit code $LASTEXITCODE." }

Invoke-NativeCommand "INSTALL FRONTEND DEPENDENCIES" { npm ci }
Invoke-NativeCommand "NPM SECURITY AUDIT" { npm audit --audit-level=moderate }
Invoke-NativeCommand "PRODUCTION FRONTEND BUILD" { npm run build }

$testingDb = Join-Path (Get-Location) 'database\release-verification.sqlite'
if (Test-Path $testingDb) { Remove-Item $testingDb -Force }
New-Item -ItemType File -Path $testingDb -Force | Out-Null

$previousConnection = $env:DB_CONNECTION
$previousDatabase = $env:DB_DATABASE

try {
    $env:DB_CONNECTION = 'sqlite'
    $env:DB_DATABASE = $testingDb

    Invoke-NativeCommand "CLEAR TEST CONFIG" { php artisan config:clear }
    Invoke-NativeCommand "FRESH SQLITE MIGRATION" { php artisan migrate:fresh --force }
    Invoke-NativeCommand "SQLITE ROLLBACK" { php artisan migrate:rollback --force }
    Invoke-NativeCommand "SQLITE MIGRATION WITH DEMO DATA" { php artisan migrate:fresh --seed --force }
    Invoke-NativeCommand "AUTOMATED TESTS" { php artisan test }
    Invoke-NativeCommand "LARAVEL PINT" { php vendor/bin/pint --test }
}
finally {
    if ($null -eq $previousConnection) { Remove-Item Env:DB_CONNECTION -ErrorAction SilentlyContinue } else { $env:DB_CONNECTION = $previousConnection }
    if ($null -eq $previousDatabase) { Remove-Item Env:DB_DATABASE -ErrorAction SilentlyContinue } else { $env:DB_DATABASE = $previousDatabase }
    Remove-Item $testingDb -Force -ErrorAction SilentlyContinue
    php artisan config:clear | Out-Null
}

Invoke-NativeCommand "FINAL CACHE CLEAR" { php artisan optimize:clear }

Write-Host "`nCLASSBRIDGE RELEASE VERIFICATION PASSED" -ForegroundColor Green
