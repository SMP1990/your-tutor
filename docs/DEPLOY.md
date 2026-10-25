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
The real Laravel app runs on Hostinger shared hosting (see the next section). The old static preview
(`site:export`, git branch `static-site`) was removed in Step 7a; the branch only remains on GitHub for reference.

## First admin account
The website can never create an admin, so make the first one on the server (SSH, inside the site's `app/` folder):

```
/opt/alt/php84/usr/bin/php artisan admin:create admin@your-domain.com --name="Full Name"
```

It asks for the password twice (hidden; 12+ characters with letters and numbers). Never put the password in the
command. Admin login also asks for a code that is sent by email: until live email works (`MAIL_MAILER=smtp`, step
1f-5) the code only lands in `storage/logs/laravel.log` on the server.

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
   (first time only: `php artisan storage:link` so uploaded profile photos are served from `/storage`)
4. Check the live pages.

**Roll back to the static site:** in `~/domains/<site>/`: `rm public_html && cp -a public_html_static_backup public_html`.
**Back to Laravel:** `rm -rf public_html && ln -s app/public public_html`.

Things that only show up in production (cached config/routes): never call `env()` outside `config/`,
and never register bindings or other setup in `routes/web.php` (use a service provider).

## Temporary preview helpers: removed in Step 7a
The sample dashboards (`/preview/{role}`), their links on the Log In page, the `/verify-phone` design page and
the `site:export` command are gone from the code. Still to do by hand, only when the owner says so: delete the
`static-site` branch on GitHub and the local `website/` and `upload-to-hostinger*` folders next to `laravel-app`.
The server still has `public_html_static_backup/` (old static site); delete it once rollback to it is never needed.

## Launch switches
- `SITE_INDEXABLE=true`: lets search engines index the site (robots.txt allows, noindex tag removed).
  Keep `false` while the site shows dummy data.
- `APP_ENV=production`, `APP_DEBUG=false`, real `APP_URL`.
- After launch: submit `https://<domain>/sitemap.xml` in Google Search Console.
