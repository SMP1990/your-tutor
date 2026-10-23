# your tutor

Online tutoring marketplace (Pakistan first). Laravel + MySQL.

- Rules for working on this project: `CLAUDE.md`
- Plan: `docs/PLAN.md`
- Going live: `docs/LAUNCH_CHECKLIST.md`
- Deploying: `docs/DEPLOY.md`

## Run locally
```
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```
