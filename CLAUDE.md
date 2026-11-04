# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

"HUB Training SSO Server": a Laravel 10 (PHP ^8.1) app that acts as an OAuth2 single-sign-on provider (Laravel Passport) for driving-school training systems (I-Prompt, KST-Plus, etc.). It also has a small Bootstrap/Blade/Livewire admin UI for managing customers (students), staff, agencies, branches and courses. UI text and much of the data (branch/course names) is Thai.

Detailed Thai-language documentation (architecture, database, SSO/API, roles, data sync, known issues) lives in `docs/` — start at `docs/README.md`.

## Commands

```bash
composer install && npm install
cp .env.example .env          # MySQL expected: DB_DATABASE=sso_hub_server_db
php artisan key:generate
php artisan passport:keys     # first-time Passport setup
php artisan passport:client   # create an OAuth client
php artisan migrate --seed    # DatabaseSeeder -> RoleSeeder (admin/staff/customer) + UserSeeder

php artisan serve             # backend
npm run dev                   # vite (resources/sass/app.scss, resources/js/app.js)
npm run build

php artisan test                                   # PHPUnit
php artisan test --filter=ExampleTest              # single test
./vendor/bin/pint                                  # code style (laravel/pint)
```

Tests only contain the default Laravel examples; `phpunit.xml` has the sqlite lines commented out, so tests run against whatever DB `.env` points at.

Data-sync / maintenance artisan commands (listed in README):

- `php artisan fetch:apidata {idmskk|idmsLLK|idmsMK|idmsPRO|idmsPY|idmsTK}` — pulls today's students/courses from `dsmsys.net/<dest>/tz/` for one branch
- `php artisan fetch:posdata` — same idea from `pos.trainingzenter.com/api/student/<date>`
- `php artisan update:database-normalize` — rewrites `users.brn`/`users.agn` from names/ids to `brn_id`/`agn_id`
- `php artisan update:coursetype`, `php artisan update:usercourse` — backfill course types / user-course links

## Architecture

**Two entry surfaces**
- `routes/web.php` — session-auth admin UI (`Auth::routes()` from laravel/ui). Most logic lives in the fat controllers `HomeController` and `CustomerController`; views are in `resources/views/pages` and `resources/views/livewire` (one Livewire component, `UserPermission`, for per-user Spatie permissions).
- `routes/api.php` — the SSO resource endpoints, guard `auth:api` (Passport) with token scopes declared in `AuthServiceProvider` (`view-user`, `i-prompt`, `kst-plus`). `/api/user` returns name, role, latest course type, branch and agency for the downstream app. `/api/logmeout` revokes all of the user's access/refresh tokens. `/api/courses` uses HTTP basic auth; `/api/courseType` is public. Route logic is inline closures, not controllers. Tokens expire after 1 day (refresh 30 days).
- Custom Passport client model `App\Models\Passport` overrides `skipsAuthorization()` to always show the consent screen (view: `resources/views/vendor/passport/authorize.blade.php`).

**Authorization** is two-layered: the legacy `users.role` string column (`admin`/`staff`/`customer`, checked by `/api/user`) *and* Spatie roles (checked by the `admin` and `adminAndStaff` route middleware via `hasRole`). Both must be kept in sync when creating users. Staff customer listings are scoped by `Auth::user()->brn`; admins see all.

**Domain model** — customers and staff are all rows in `users` (soft-deleted; "deleted customers" can be reused or permanently purged by admins). `users.username` is the national ID/passport number used to log in, and the initial password is that same value. Relations use business keys, not primary keys: `users.brn → branches.brn_id`, `users.agn → agencies.agn_id`, `courses.agn → agencies.agn_id`, whereas `branches.agn → agencies.id`. `User_has_course` (soft-deleted pivot) links users to courses; the *most recent* link determines the course type returned by the API and the vehicle type (car/motobike/trailer) chosen on the home page. `users.nationality` (`TH`/`EN`/`PM`) is inferred from the student code format in the fetch commands.

**Branch mapping is hard-coded in two places** that must stay in sync: the `switch ($apiDest)` in `FetchApiDataToDatabase` (API destination → branch name/`IDD000x` id) and the Thai-substring matching in `HomeController::index` (branch name → `send_branch` code). Adding a branch means editing both.

## Gotchas

- `mynote.txt` and the fetch commands contain committed credentials (an OAuth client secret, a Basic-auth header). Don't copy them elsewhere or add more.
- Commands print with `echo` and swallow exceptions per-record rather than failing the run.
- `HomeController` imports a non-existent `App\Models\Department`.
