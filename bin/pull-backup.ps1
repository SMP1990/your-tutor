# Copies the newest server backup to this PC (Step 7c-2). Started every day by the scheduled task that
# install-pc-backup.ps1 creates. Needs the SSH key and ~/.ssh/config.yourtutor that already exist on this PC.
# Backups go to %USERPROFILE%\YourTutorBackups (only your Windows user can open that folder). The 14 newest are kept.
$ErrorActionPreference = 'Stop'
$dest = Join-Path $env:USERPROFILE 'YourTutorBackups'
$cfg = Join-Path $env:USERPROFILE '.ssh\config.yourtutor'
$keep = 14
$log = Join-Path $dest 'pull.log'

function Write-Log([string]$text) {
    Add-Content -Path $log -Value ('{0} {1}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $text)
    Write-Host $text
}

try {
    New-Item -ItemType Directory -Force -Path $dest | Out-Null

    $latest = & ssh -F $cfg yourtutor 'cat backups/latest.txt'
    if ($LASTEXITCODE -ne 0) { throw 'could not read backups/latest.txt on the server (is the server backup running?)' }

    $names = @($latest | ForEach-Object { "$_".Trim() } | Where-Object { $_ })
    if ($names.Count -ne 2) { throw "latest.txt has $($names.Count) lines, expected 2" }

    foreach ($name in $names) {
        # Only ever copy files that look exactly like our backups.
        if ($name -notmatch '^(db|files)-\d{4}-\d{2}-\d{2}_\d{4}\.(sql|tar)\.gz$') { throw "unexpected file name: $name" }
        $target = Join-Path $dest $name
        if (Test-Path $target) { continue }

        & scp -F $cfg "yourtutor:backups/$name" "$target.part"
        if ($LASTEXITCODE -ne 0) { throw "copying $name failed" }
        if ((Get-Item "$target.part").Length -lt 1000) { throw "$name is too small, not keeping it" }
        Move-Item -Force "$target.part" $target
        Write-Log "copied $name ($([math]::Round((Get-Item $target).Length / 1KB)) KB)"
    }

    foreach ($pattern in 'db-*.sql.gz', 'files-*.tar.gz') {
        Get-ChildItem -Path $dest -Filter $pattern | Sort-Object Name -Descending | Select-Object -Skip $keep | Remove-Item -Force
    }
    Write-Log "OK, newest backup: $($names[0])"
}
catch {
    Write-Log "FAILED: $_"
    exit 1
}
