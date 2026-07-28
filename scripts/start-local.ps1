param(
    [string]$ProjectPath = ""
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($ProjectPath)) {
    $ProjectPath = Split-Path -Parent $PSScriptRoot
}

Set-Location -LiteralPath (Resolve-Path -LiteralPath $ProjectPath).Path
$env:Path = "C:\xampp\php;C:\xampp\mysql\bin;$env:Path"

if (-not (Test-Path -LiteralPath '.env')) {
    throw ".env is missing. Run .\SETUP-CLASSBRIDGE.ps1 first."
}

if (-not (Test-Path -LiteralPath 'vendor\autoload.php')) {
    throw "Composer dependencies are missing. Run .\SETUP-CLASSBRIDGE.ps1 first."
}

if (-not (Test-Path -LiteralPath 'public\build\manifest.json')) {
    throw "Frontend assets are missing. Run .\SETUP-CLASSBRIDGE.ps1 first."
}

php artisan optimize:clear
if ($LASTEXITCODE -ne 0) {
    throw "Could not clear Laravel caches."
}

php artisan serve --host=127.0.0.1 --port=8000 --no-reload
