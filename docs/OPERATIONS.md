# Operations

## Worker health

A running worker prints the execution IDs it claims. In production, keep it under systemd or another supervisor. The included systemd unit restarts the process after failures.

If daemon processes are unavailable, run `php cli/worker.php --once` from cron. The worker lock prevents two local worker processes from executing simultaneously.

## Diagnostics

Run:

```text
php cli/doctor.php
```

It checks the PHP version, PDO driver, OpenSSL, writable storage, encryption key, and database connection.

The unauthenticated `GET /health` endpoint performs a lightweight database check and returns HTTP 200 or 503. Keep detailed infrastructure monitoring outside Patchbay.

## Retention

`php cli/prune.php` deletes events older than the configured retention period. Executions and step logs belonging to those events are deleted by foreign-key cascade. API keys, workflows, secrets, users, settings, and audit records are retained.

## Backups

Back up the database and the encryption key together. If `APP_KEY` is blank, Patchbay creates `storage/app.key`; losing that file makes encrypted workflow signing secrets and vault entries unreadable.

For SQLite, include the database's WAL state or use SQLite-aware online backup tooling while Patchbay is active. For MySQL, use your normal transactional backup process.

## Common symptoms

- **Events arrive but nothing executes:** the worker is not running.
- **HTTP actions repeatedly queue:** inspect the execution timeline for response codes or transport errors.
- **A browser webhook fails preflight:** add its exact scheme/host/port to Allowed origins. Server-to-server calls without an Origin header are not blocked by that setting.
- **Encrypted values stop working after a server move:** restore the original `APP_KEY` or `storage/app.key`.
- **SQLite reports permission errors:** the PHP user needs write access to `storage/database/` and `storage/cache/`.
