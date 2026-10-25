# One-time setup on the owner's PC (Step 7c-2). It
#   - copies pull-backup.ps1 to %LOCALAPPDATA%\YourTutor (a few KB; the backups themselves go to -Destination),
#   - makes the backup folder private to this Windows user (if the drive supports it),
#   - creates a daily scheduled task "YourTutor backup copy" (13:00; if the PC was off, it runs when the PC is on again),
#   - moves backups from the first version's folder (C:\Users\<you>\YourTutorBackups) to the new one,
#   - runs the copy once so you can see that it works.
# Run:  bin\install-pc-backup.cmd            (backups go to F:\YourTutorBackups)
#       bin\install-pc-backup.cmd E:\YourTutorBackups     (another drive or folder)
param(
    [string]$Destination = 'F:\YourTutorBackups'
)
$ErrorActionPreference = 'Stop'
$programDir = Join-Path $env:LOCALAPPDATA 'YourTutor'
$script = Join-Path $programDir 'pull-backup.ps1'
$taskName = 'YourTutor backup copy'
$oldDir = Join-Path $env:USERPROFILE 'YourTutorBackups'

$root = Split-Path -Qualifier $Destination
if (-not (Test-Path $root)) { throw "Drive $root does not exist on this PC. Pick another one, e.g.: install-pc-backup.cmd E:\YourTutorBackups" }

New-Item -ItemType Directory -Force -Path $programDir, $Destination | Out-Null
Copy-Item -Force (Join-Path $PSScriptRoot 'pull-backup.ps1') $script

# Only this Windows user (and SYSTEM) may open the backups: they contain ID documents and children's data.
& icacls $Destination /inheritance:r /grant:r "$($env:USERNAME):(OI)(CI)F" 'SYSTEM:(OI)(CI)F' | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Warning "$root cannot restrict who opens the folder (probably a FAT/exFAT disk). Do not share this drive."
}

# Move what the first version put on C: so there is one place only.
if ((Test-Path $oldDir) -and ((Resolve-Path $oldDir).Path -ne (Resolve-Path $Destination).Path)) {
    Get-ChildItem -Path $oldDir -File | Where-Object { $_.Name -match '^(db|files)-.*\.gz$' -or $_.Name -eq 'pull.log' } |
        ForEach-Object { Move-Item -Force $_.FullName (Join-Path $Destination $_.Name) }
    Remove-Item -Force (Join-Path $oldDir 'pull-backup.ps1') -ErrorAction SilentlyContinue
    Get-ChildItem -Path $oldDir -Filter '*.part' -File | Remove-Item -Force -ErrorAction SilentlyContinue
    if (-not (Get-ChildItem -Path $oldDir -Force)) { Remove-Item -Force $oldDir }
}

$action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$script`" -Destination `"$Destination`""
$trigger = New-ScheduledTaskTrigger -Daily -At '13:00'
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -ExecutionTimeLimit (New-TimeSpan -Minutes 30)
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Description 'Copies the newest your-tutor server backup to this PC' -Force | Out-Null
Write-Host "Scheduled task '$taskName' created (daily 13:00). Backups go to $Destination"

Write-Host 'Running the copy once now...'
& powershell.exe -NoProfile -ExecutionPolicy Bypass -File $script -Destination $Destination
