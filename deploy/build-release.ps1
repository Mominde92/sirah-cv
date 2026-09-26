# Builds a ready-to-upload release for cPanel (no Node or Composer needed on the server).
# Usage (from the project root):  powershell -ExecutionPolicy Bypass -File deploy\build-release.ps1
# Output: release\sirah-release-<date>.zip

param(
    [string]$Php = 'C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe',
    [string]$Composer = 'C:\laragon\bin\composer\composer.phar'
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$stage = Join-Path $root 'release\stage'
$stamp = Get-Date -Format 'yyyyMMdd-HHmm'
$zip = Join-Path $root "release\sirah-release-$stamp.zip"

Write-Host '1/5 Building front-end assets...'
Push-Location $root
$ErrorActionPreference = 'Continue'
npm run build 2>&1 | Out-Null
$npmExit = $LASTEXITCODE
$ErrorActionPreference = 'Stop'
Pop-Location
if ($npmExit -ne 0) { throw 'npm run build failed.' }

Write-Host '2/5 Copying application files...'
if (Test-Path $stage) { Remove-Item $stage -Recurse -Force }
New-Item -ItemType Directory -Force $stage | Out-Null

$excludeDirs = @('node_modules', '.git', 'vendor', 'release', 'tests', 'mobile', '.idea', '.vscode', '.claude')
$excludeFiles = @('.env', '.env.backup', '*.log', '.phpunit.result.cache')
robocopy $root $stage /E /NFL /NDL /NJH /NJS /NP /XD $excludeDirs /XF $excludeFiles | Out-Null

# Runtime folders must exist but start empty (no local uploads, sessions or caches).
foreach ($dir in @('storage\app\private', 'storage\app\public', 'storage\framework\cache\data', 'storage\framework\sessions', 'storage\framework\views', 'storage\logs', 'bootstrap\cache')) {
    $path = Join-Path $stage $dir
    if (Test-Path $path) { Get-ChildItem $path -Recurse -Force -Exclude '.gitignore' | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue }
    New-Item -ItemType Directory -Force $path | Out-Null
}
Remove-Item (Join-Path $stage 'public\storage') -Force -Recurse -ErrorAction SilentlyContinue
Remove-Item (Join-Path $stage 'public\hot') -Force -ErrorAction SilentlyContinue

Write-Host '3/5 Installing production dependencies...'
# Start from the local vendor folder: downloading and extracting hundreds of packages again
# is slow and on Windows often hits antivirus / indexer file locks.
robocopy (Join-Path $root 'vendor') (Join-Path $stage 'vendor') /E /NFL /NDL /NJH /NJS /NP | Out-Null

Push-Location $stage
# Composer and artisan report progress on stderr; Windows PowerShell would treat that as a fatal error.
$ErrorActionPreference = 'Continue'
$composerExit = 1
for ($attempt = 1; $attempt -le 3 -and $composerExit -ne 0; $attempt++) {
    # Removes dev-only packages (phpunit, faker, ...) and writes an optimized autoloader.
    & $Php $Composer install --no-dev -o -n --no-progress --no-scripts 2>&1 | Out-Null
    $composerExit = $LASTEXITCODE
    if ($composerExit -ne 0) { Start-Sleep -Seconds 3 }
}
& $Php artisan package:discover 2>&1 | Out-Null
$ErrorActionPreference = 'Stop'
if ($composerExit -ne 0 -or -not (Test-Path 'vendor\autoload.php')) { Pop-Location; throw 'composer install failed: the release would have no vendor folder.' }

# mPDF ships ~90 MB of fonts; the app embeds its own (public/fonts) and only needs
# DejaVu (glyph fallback) and XB Riyaz. Removing the rest shrinks the upload a lot.
Get-ChildItem 'vendor\mpdf\mpdf\ttfonts' -File |
    Where-Object { $_.Name -notmatch '^(DejaVuSans|DejaVuSansCondensed|DejaVuSerif|XB Riyaz)' } |
    Remove-Item -Force
Pop-Location

Write-Host '4/5 Compressing...'
if (Test-Path $zip) { Remove-Item $zip -Force }
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($stage, $zip, [System.IO.Compression.CompressionLevel]::Optimal, $false)

Write-Host '5/5 Cleaning up...'
Remove-Item $stage -Recurse -Force

$size = [math]::Round((Get-Item $zip).Length / 1MB, 1)
Write-Host "Done: $zip ($size MB)"
