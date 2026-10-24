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

## Hostinger shared hosting (current setup)
Server access details are in the owner's private notes, never in this repo.

Layout inside the site's folder (`~/domains/<site>/`):
- `app/`: the Laravel app (outside the web root). Its `.env` holds all secrets (owner pastes passwords/keys).
- `public_html` → symlink to `app/public` (the web root).
- `public_html_static_backup/`: the old static preview, kept for rollback.

Use PHP 8.4 for every command: `/opt/alt/php84/usr/bin/php` (the default `php` over SSH is older).

**Deploy an update** (from the local `laravel-app`, after `php artisan test` passes and `npm run build`):
1. `git archive --format=tar.gz -o app.tar.gz HEAD` and, if CSS/JS changed, `tar --force-local -czf build.tar.gz -C public build`
2. Upload both to `~/domains/<site>/`, then: `tar -xzf app.tar.gz -C app` (and `tar -xzf build.tar.gz -C app/public`)
3. In `app/`: `composer install --no-dev --optimize-autoloader` (only if composer.lock changed),
   `php artisan migrate --force`, `php artisan optimize`
4. Check the live pages.

**Roll back to the static site:** in `~/domains/<site>/`: `rm public_html && cp -a public_html_static_backup public_html`.
**Back to Laravel:** `rm -rf public_html && ln -s app/public public_html`.

Things that only show up in production (cached config/routes): never call `env()` outside `config/`,
and never register bindings or other setup in `routes/web.php` (use a service provider).

## Remove before launch (temporary preview helpers)
- `resources/views/partials/dashboard-preview-links.blade.php`, its `@include` in `auth/login.blade.php`
  and the `.preview-links` CSS: quick links to the sample dashboards.
- `DashboardPreviewController` (`/preview/{role}`) and `AuthPreviewController` (`/verify-phone`) with their routes.
- `site:export` command and the `static-site` branch, once the Laravel app itself is deployed.

## Launch switches
- `SITE_INDEXABLE=true`: lets search engines index the site (robots.txt allows, noindex tag removed).
  Keep `false` while the site shows dummy data.
- `APP_ENV=production`, `APP_DEBUG=false`, real `APP_URL`.
- After launch: submit `https://<domain>/sitemap.xml` in Google Search Console.
