param([string]$OutputPath = '', [string]$Ref = 'HEAD')
$ErrorActionPreference = 'Stop'
$repoPath = Split-Path -Parent $PSScriptRoot
Push-Location -LiteralPath $repoPath
try {
    $commit = git rev-parse --verify "$Ref^{commit}"
    if ($LASTEXITCODE -ne 0) { throw 'The release ref must resolve to a commit.' }
    if (-not $OutputPath) { $OutputPath = Join-Path $repoPath ('metransfers-' + $commit.Substring(0, 12) + '.zip') }
    $OutputPath = [IO.Path]::GetFullPath($OutputPath)
    if (Test-Path -LiteralPath $OutputPath) { throw "Output already exists: $OutputPath" }
    git archive --format=zip --prefix=metransfers/ "--output=$OutputPath" $commit
    if ($LASTEXITCODE -ne 0) { throw 'git archive failed.' }
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $archive = [IO.Compression.ZipFile]::OpenRead($OutputPath)
    try {
        $names = @($archive.Entries | ForEach-Object FullName)
        $unexpected = @($names | Where-Object { $_ -match '^metransfers/(\.git/|\.github/|vendor/|node_modules/|\.phpstan-cache/|\.phpunit.cache/|test-results/|tests/|tools/|docs/|fix_[^/]*\.php$)' })
        if ($unexpected.Count) { throw ('Unexpected development files: ' + ($unexpected -join ', ')) }
        foreach ($required in @('style.css', 'functions.php', 'index.php', 'app/Bootstrap.php')) {
            if ($names -notcontains "metransfers/$required") { throw "Missing runtime file: $required" }
        }
    } finally { $archive.Dispose() }
    Write-Output "Commit: $commit"
    Write-Output "Release: $OutputPath"
    Get-FileHash -LiteralPath $OutputPath -Algorithm SHA256
} finally { Pop-Location }
