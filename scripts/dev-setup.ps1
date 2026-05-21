#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Local development setup for jobFlowMu (Windows).
.DESCRIPTION
    Requires PHP 8.4+ in PATH (Laravel 13 / Symfony 8).
#>
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $root

Write-Host "Project: $root" -ForegroundColor Cyan

$phpVersion = php -r "echo PHP_VERSION;" 2>$null
if (-not $phpVersion) {
    Write-Error 'PHP not found. Add PHP 8.4+ to PATH or install Laravel Herd.'
    exit 1
}

$major = [int](php -r "echo PHP_MAJOR_VERSION;")
$minor = [int](php -r "echo PHP_MINOR_VERSION;")
if ($major -lt 8 -or ($major -eq 8 -and $minor -lt 4)) {
    Write-Host "Current PHP: $phpVersion" -ForegroundColor Yellow
    Write-Error 'This project needs PHP 8.4 or newer. XAMPP PHP 8.2 is not enough.'
    Write-Host ''
    Write-Host 'Options:' -ForegroundColor Green
    Write-Host '  1) https://herd.laravel.com/windows (easy PHP switch)'
    Write-Host '  2) https://windows.php.net/download/ (ZIP, put php.exe before XAMPP in PATH)'
    exit 1
}

Write-Host "PHP $phpVersion - OK" -ForegroundColor Green

if (-not (Test-Path -LiteralPath "$root\.env")) {
    Copy-Item -LiteralPath "$root\.env.example" -Destination "$root\.env"
    Write-Host 'Created .env from .env.example' -ForegroundColor Green
} else {
    Write-Host '.env already exists' -ForegroundColor Gray
}

composer install --no-interaction
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

php artisan key:generate --no-interaction
npm install
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host ''
Write-Host 'Check MySQL in .env: DB_DATABASE (default jobflowmu), DB_USERNAME, DB_PASSWORD.' -ForegroundColor Yellow
Write-Host 'Running migrations...' -ForegroundColor Cyan
php artisan migrate --force
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host ''
Write-Host 'Next: terminal 1 -> npm run dev' -ForegroundColor Cyan
Write-Host '       terminal 2 -> php artisan serve' -ForegroundColor Cyan
Write-Host '       or once:     composer run dev' -ForegroundColor Cyan
Write-Host ''
Write-Host 'AI interviews: set OPENAI_API_KEY in .env (see config/ai.php).' -ForegroundColor Yellow
