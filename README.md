<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## BrewCloud Versioning and Releases

- `APP_VERSION` defines the currently deployed app version.
- Latest release data is pulled from GitHub Releases via:
	- `GITHUB_REPO` (supports `owner/repo` or full GitHub repository URL)
	- `GITHUB_TOKEN` (optional, recommended for higher API limits)
	- `GITHUB_RELEASE_CACHE_MINUTES` (cache TTL in minutes)

### Environment Variables

```env
APP_VERSION=dev
GITHUB_REPO=your-org/cofeesaas
GITHUB_TOKEN=
GITHUB_RELEASE_CACHE_MINUTES=15
```

### Release Flow

1. Tag and push a new version.
2. Create a GitHub Release for that tag.
3. Set production `APP_VERSION` to the deployed tag.
4. Super Admin Dashboard will show current version, latest release, and update status.

## Run BrewCloud on Another PC

### 1) Install Prerequisites

- PHP 8.2+
- Composer
- Node.js 20+ and npm
- MySQL 8+

### 2) Clone and Setup

```bash
git clone <your-repo-url> brewcloud
cd brewcloud
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
npm run build
```

### 3) Configure Domain for Multi-Tenant Subdomains

Set these values in `.env` on each PC:

```env
APP_URL=http://brewcloud.test:8000/
APP_DOMAIN=brewcloud.test
SESSION_DOMAIN=.brewcloud.test
```

Then ensure local DNS/hosts resolves:

- `brewcloud.test`
- `*.brewcloud.test`

### 4) Run Required Workers

Your update notifications and other async tasks use queues. Run this on each environment:

```bash
php artisan queue:work --tries=3 --timeout=120
```

If you use a process manager, keep this worker always running.

## Sync Downloaded/New Version

When you pull or download a new version, run the sync script inside the project root.

### Windows (PowerShell)

```powershell
./scripts/update-and-sync.ps1
```

Optional: deploy a specific release tag from Git:

```powershell
./scripts/update-and-sync.ps1 -ReleaseTag v1.0.7
```

### Linux/macOS

```bash
chmod +x ./scripts/update-and-sync.sh
./scripts/update-and-sync.sh
```

Optional specific tag:

```bash
./scripts/update-and-sync.sh v1.0.7
```

What the script does:

1. Puts app in maintenance mode
2. Attempts a DB backup (if `mysqldump` exists)
3. Pulls or checks out release code (Git repositories)
4. Installs dependencies
5. Runs migrations
6. Builds frontend assets
7. Refreshes Laravel caches
8. Restarts queue workers
9. Brings app back online

## Automatic Release Polling

BrewCloud now includes a release sync command:

```bash
php artisan updates:sync-latest --publish
```

- Detects latest GitHub release
- Prevents duplicate processing using cached `last_seen_release`
- Queues tenant OTA update metadata notifications when new release is detected

Scheduler is enabled by default via:

```env
RELEASE_SYNC_ENABLED=true
RELEASE_SYNC_CRON=*/15 * * * *
```

Make sure Laravel scheduler runs every minute:

```bash
php artisan schedule:run
```

For production, configure a system cron/task to run scheduler every minute.

## Tenant Click-to-Update (Deploy from GitHub)

When a shop owner clicks update, BrewCloud can now queue a real server-side sync from GitHub.

Enable this in `.env`:

```env
UPDATER_ENABLED=true
TENANT_CAN_TRIGGER_UPDATER=true
UPDATER_BRANCH=main
UPDATER_TIMEOUT_SECONDS=1800
UPDATER_LOCK_SECONDS=3600
```

Behavior:

1. Tenant owner clicks update on the updates page.
2. App validates selected tag is the latest release.
3. A queued job runs the update script (`scripts/update-and-sync.ps1` or `scripts/update-and-sync.sh`).
4. Tenant OTA status is updated to `queued`, `running`, `applied`, or `failed`.

Important:

- Keep `TENANT_CAN_TRIGGER_UPDATER=false` if only platform admins should deploy code.
- Ensure queue worker is always running, otherwise update jobs will stay queued.
