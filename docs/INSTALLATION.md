# Installation

Patchbay runs on PHP 8.2+ with no framework, Composer packages, Node build, or external queue service.

## Requirements

- PHP 8.2 or newer
- PDO plus `pdo_sqlite` (default) or `pdo_mysql`
- OpenSSL
- cURL is recommended; PHP streams are used as a fallback
- A writable `storage/` directory
- A long random `APP_KEY` (recommended) or permission to create `storage/app.key`
- A long-running worker process or a cron entry that calls the worker in `--once` mode

## Quick local setup

1. Copy `.env.example` to `.env`.
2. Set `APP_URL`. You can set `APP_KEY` yourself; otherwise Patchbay generates a private `storage/app.key` on first boot.
3. Run `php cli/doctor.php`.
4. Run `php cli/migrate.php`.
5. Start the web app with `php -S 127.0.0.1:8080 -t public public/router.php`.
6. In a second terminal run `php cli/worker.php`.
7. Open `http://127.0.0.1:8080/setup` and create the owner account.

SQLite data is stored in `storage/database/patchbay.sqlite` by default and is intentionally excluded from Git.

## MySQL

Set `DB_DRIVER=mysql` and configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD`. Run `php cli/migrate.php` after changing drivers.

## Production notes

Point the web server document root at `public/`, never the repository root. Keep `.env`, `storage/`, `src/`, and `config/` outside the public document root. Set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE=true` when HTTPS is enabled, and run the worker under a process supervisor.
