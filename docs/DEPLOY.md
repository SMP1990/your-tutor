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
The static preview lives on git branch `static-site` and is uploaded by hand as a zip to
Hostinger shared hosting (`public_html`). Build it with:

```
npm run build
php artisan site:export --url=https://<current domain>
```

`--url` is only used for the canonical tag, link previews (og:image) and sitemap.xml.
Links between pages are relative, so the files work on any domain. Export again after a domain change.
The export writes `.htaccess` with HTTPS redirect, security headers (same as
`App\Http\Middleware\SecurityHeaders`) and the custom 404 page.

## Launch switches
- `SITE_INDEXABLE=true`: lets search engines index the site (robots.txt allows, noindex tag removed).
  Keep `false` while the site shows dummy data.
- `APP_ENV=production`, `APP_DEBUG=false`, real `APP_URL`.
- After launch: submit `https://<domain>/sitemap.xml` in Google Search Console.
