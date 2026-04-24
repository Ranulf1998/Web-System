param(
    [string]$ReleaseTag = "",
    [string]$Branch = "main",
    [switch]$SkipGit,
    [switch]$SkipFrontend,
    [string]$PhpBin = "php",
    [string]$ComposerBin = "composer",
    [string]$NpmBin = "npm"
)

$ErrorActionPreference = "Stop"
$repoRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
Set-Location $repoRoot

function Write-Step {
    param([string]$Message)
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Get-EnvValue {
    param(
        [string]$Path,
        [string]$Key,
        [string]$Default = ""
    )

    if (-not (Test-Path $Path)) {
        return $Default
    }

    $line = Get-Content $Path | Where-Object { $_ -match "^$Key=" } | Select-Object -First 1
    if (-not $line) {
        return $Default
    }

    $value = $line.Substring($Key.Length + 1).Trim()
    if ($value.StartsWith('"') -and $value.EndsWith('"')) {
        $value = $value.Substring(1, $value.Length - 2)
    }

    return $value
}

if (-not (Test-Path ".env")) {
    throw ".env file is missing. Copy .env.example to .env before running this script."
}

if (-not (Test-Path "artisan")) {
    throw "artisan file not found. Run this script from the BrewCloud project."
}

$maintenanceUp = $false

try {
    if ((Test-Path ".git") -and (-not $SkipGit)) {
        Write-Step "Fetching Git tags and commits"
        git fetch --all --tags

        if ($LASTEXITCODE -ne 0) {
            throw "git fetch failed."
        }

        if ($ReleaseTag -ne "") {
            Write-Step "Checking out release tag $ReleaseTag"
            git checkout "tags/$ReleaseTag"
        }
        else {
            Write-Step "Pulling latest changes from origin/$Branch"
            git checkout $Branch
            git pull origin $Branch
        }
    }

    Write-Step "Putting app in maintenance mode"
    & $PhpBin artisan down
    $maintenanceUp = $true

    Write-Step "Backing up central database (best effort)"
    $dbConnection = Get-EnvValue -Path ".env" -Key "DB_CONNECTION" -Default "mysql"
    if ($dbConnection -eq "mysql") {
        $backupDir = Join-Path "storage\app" "backups"
        New-Item -Path $backupDir -ItemType Directory -Force | Out-Null

        $dbHost = Get-EnvValue -Path ".env" -Key "DB_HOST" -Default "127.0.0.1"
        $dbPort = Get-EnvValue -Path ".env" -Key "DB_PORT" -Default "3306"
        $dbName = Get-EnvValue -Path ".env" -Key "DB_DATABASE"
        $dbUser = Get-EnvValue -Path ".env" -Key "DB_USERNAME"
        $dbPass = Get-EnvValue -Path ".env" -Key "DB_PASSWORD"

        if ($dbName -ne "" -and $dbUser -ne "") {
            $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
            $backupFile = Join-Path $backupDir "central-$timestamp.sql"
            $dumpArgs = @("-h$dbHost", "-P$dbPort", "-u$dbUser")

            if ($dbPass -ne "") {
                $dumpArgs += "-p$dbPass"
            }

            $dumpArgs += @($dbName, "--result-file=$backupFile")

            $dump = Get-Command mysqldump -ErrorAction SilentlyContinue
            if ($null -ne $dump) {
                & mysqldump @dumpArgs
                if ($LASTEXITCODE -eq 0) {
                    Write-Host "Created DB backup: $backupFile"
                }
                else {
                    Write-Warning "mysqldump returned a non-zero exit code. Continuing update."
                }
            }
            else {
                Write-Warning "mysqldump not found in PATH. Skipping DB backup."
            }
        }
    }

    Write-Step "Installing PHP dependencies"
    & $ComposerBin install --no-interaction --prefer-dist --optimize-autoloader

    Write-Step "Running database migrations"
    & $PhpBin artisan migrate --force

    if ((-not $SkipFrontend) -and (Test-Path "package.json")) {
        Write-Step "Installing JS dependencies"
        & $NpmBin ci

        Write-Step "Building frontend assets"
        & $NpmBin run build
    }

    Write-Step "Refreshing caches"
    & $PhpBin artisan optimize:clear
    & $PhpBin artisan config:cache
    & $PhpBin artisan route:cache
    & $PhpBin artisan view:cache

    Write-Step "Restarting queue workers"
    & $PhpBin artisan queue:restart
}
finally {
    if ($maintenanceUp) {
        Write-Step "Bringing app back online"
        & $PhpBin artisan up
    }
}

Write-Host "Update and sync completed." -ForegroundColor Green
