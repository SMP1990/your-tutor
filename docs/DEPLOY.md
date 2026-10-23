# Deploy

To be filled in when the Laravel app first goes to a server (Hostinger VPS planned).

## Will cover
- Server requirements (PHP 8.3+, MySQL, extensions)
- First install (upload, `.env`, database, `php artisan migrate --force`, `storage:link`)
- Cron for the scheduler: `* * * * * php /path/to/artisan schedule:run`
- Queue worker (keeps running in the background)
- Releases: git tag per release, deploy to staging first, then production
- Rollback steps
- Backups (daily, off the server) and how to restore

## Current state
The static preview (Home + Find a Tutor) lives on git branch `static-site` and is uploaded
by hand as a zip to Hostinger shared hosting (`public_html`).
