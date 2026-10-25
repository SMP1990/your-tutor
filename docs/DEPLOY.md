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
command. Admin login also asks for a code that is sent by email. Until live email works (`MAIL_MAILER=smtp`, step
1f-5) the email is NOT delivered. With `MAIL_MAILER=log` every email is written to its own daily file
`storage/logs/mail-YYYY-MM-DD.log` (channel `mail` in `config/logging.php`; debug level whatever `LOG_LEVEL` is; files
are deleted after 3 days because they hold login codes and links in plain text). To read an admin code: ask for a new
code on the code page, then on the server:
```
grep -o -E ">[0-9]{6}<" storage/logs/mail-$(date +%F).log | tail -1
```
(the newest entry is the last one; check its `To:` line is the admin's address). Before this channel existed (until the
deploy of 25 Oct) the log mailer wrote to `laravel.log` at debug level, which `LOG_LEVEL=warning` silenced; the code was
read once by switching `LOG_LEVEL` to `debug` for a minute and back. That is no longer needed.

## Email (Step 7b)
Two kinds of email:
- **At once** (never queued): the admin login code and the email-verification link. If the mail server fails the
  page still opens and says "We could not send the email right now"; the person presses the button again.
- **Queued** (job table `jobs`, needs a worker): password reset, tutor application / profile decisions, demo and booking
  answers. Each is tried up to 3 times (after 1 and 5 minutes). Without the cron below these emails WAIT and never arrive.

Every email gets `Reply-To` = the support email from Site settings. The links inside emails are built from `APP_URL`
(also in queued emails), so `APP_URL` in the server `.env` must be the real address of the site.

`.env` on the server (the owner pastes the password himself; never write it in chat or in git):
```
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
MAIL_HOST=<from the mail provider>
MAIL_PORT=<from the mail provider>
MAIL_SCHEME=<smtps for port 465, null for 587>
MAIL_USERNAME=<mailbox or SMTP login>
MAIL_PASSWORD=<paste here>
MAIL_FROM_ADDRESS="noreply@<your domain>"
MAIL_FROM_NAME="${APP_NAME}"
```
After changing `.env` run `php artisan optimize` again (config is cached).

**Cron for the queue (Hostinger hPanel, not `crontab`):** hPanel > Websites > Manage > Advanced > Cron Jobs >
custom, every minute (`* * * * *`):
```
/opt/alt/php84/usr/bin/php /home/<account>/domains/<site>/app/artisan queue:work --stop-when-empty --max-time=55 >/dev/null 2>&1
```
Check it: `php artisan queue:failed` lists emails that failed 3 times, `php artisan queue:retry all` tries them again.

**Test it:** `php artisan mail:test someone@example.com` sends one email and prints the mailer, host and from address
(never the password). It warns while `MAIL_MAILER` is still `log` or the from address is a placeholder.

## Backups (Step 7c-2)
What is saved: the whole MySQL database and the uploaded files (`storage/app/private` = CNIC and degree scans,
`storage/app/public` = photos). NOT saved: `.env` (it holds the secrets: keep your own copy in a safe place) and the
code (it is in GitHub).

**1. On the server, every day.** `bin/backup.sh` writes `db-<date>.sql.gz`, `files-<date>.tar.gz` and `latest.txt` into
`~/backups` (outside the web root), checks that both files are readable, and keeps the newest 7. Add it in hPanel >
Websites > Manage > Advanced > Cron Jobs > custom, once a day (e.g. `0 3 * * *`):
```
/bin/bash /home/<account>/domains/<site>/app/bin/backup.sh >/dev/null 2>&1
```
The result of every run (OK or FAILED and why) is a line in `~/backups/backup.log`. Run it once by hand to test:
`bash ~/domains/<site>/app/bin/backup.sh`.

**2. On the owner's PC, every day.** A server backup alone is lost if the server (or the hosting account) is lost.
Double-click `bin\install-pc-backup.cmd` once. It puts the backups in `F:\YourTutorBackups` (another drive: start it from a
command window as `install-pc-backup.cmd E:\YourTutorBackups`), makes that folder private to the Windows user and creates
the scheduled task "YourTutor backup copy" (daily 13:00; if the PC was off it runs when the PC is on again). The small
program file lives in `%LOCALAPPDATA%\YourTutor`. It uses the same SSH key as deploys. The 14 newest copies stay. Look at
`<folder>\pull.log` to see that it works. If the drive is unplugged the task FAILS and says so in `pull.log` (it never
creates the folder somewhere else). Backups from the first version (`C:\Users\<you>\YourTutorBackups`) are moved over. These files hold ID scans
and children's data: do not share the folder, do not put it in a synced cloud folder, keep the disk encrypted
(BitLocker) if the PC can be lost or stolen.

**3. Restore (never on the live database by guesswork).**
- Database: `gunzip -c db-<date>.sql.gz | mysql -h <host> -u <user> -p <database>` (this REPLACES the tables).
- Files: `tar -xzf files-<date>.tar.gz -C ~/domains/<site>/app/storage/app`.
- Then `php artisan optimize` and look at the site.
- Hostinger also keeps its own backups: hPanel > Websites > Backups (what is offered depends on the plan; check it).

**4. Practise the restore once** (needed before launch): in hPanel > Databases create an empty test database, load the
newest `db-*.sql.gz` into it, compare the table list and the row counts of `users` and `tutor_profiles` with the live
database, then delete the test database. A backup that was never restored is only a hope.

## Logs
Laravel writes one file per day (`laravel-<date>.log`, `LOG_STACK=daily`, 14 days kept). `mail-<date>.log` (3 days) holds
emails while `MAIL_MAILER=log`. If the live `.env` still says `LOG_STACK=single`, change it to `daily` and run `php artisan optimize`.

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
