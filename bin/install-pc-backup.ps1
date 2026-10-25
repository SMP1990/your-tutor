# One-time setup on the owner's PC (Step 7c-2): copies pull-backup.ps1 to %USERPROFILE%\YourTutorBackups, makes that
# folder private to this Windows user, and creates a daily scheduled task "YourTutor backup copy" (13:00; if the PC
# was off, it runs as soon as the PC is on again). Then it runs the copy once so you can see that it works.
# Run:  powershell -ExecutionPolicy Bypass -File bin\install-pc-backup.ps1
$ErrorActionPreference = 'Stop'
$dest = Join-Path $env:USERPROFILE 'YourTutorBackups'
$script = Join-Path $dest 'pull-backup.ps1'
$taskName = 'YourTutor backup copy'

New-Item -ItemType Directory -Force -Path $dest | Out-Null
Copy-Item -Force (Join-Path $PSScriptRoot 'pull-backup.ps1') $script

# Only this Windows user (and SYSTEM) may open the backups: they contain ID documents and children's data.
& icacls $dest /inheritance:r /grant:r "$($env:USERNAME):(OI)(CI)F" 'SYSTEM:(OI)(CI)F' | Out-Null

$action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$script`""
$trigger = New-ScheduledTaskTrigger -Daily -At '13:00'
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -ExecutionTimeLimit (New-TimeSpan -Minutes 30)
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Description 'Copies the newest your-tutor server backup to this PC' -Force | Out-Null
Write-Host "Scheduled task '$taskName' created (daily 13:00). Backups go to $dest"

Write-Host 'Running the copy once now...'
& powershell.exe -NoProfile -ExecutionPolicy Bypass -File $script
