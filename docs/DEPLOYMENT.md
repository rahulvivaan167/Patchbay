# Deployment

## Worker

Patchbay accepts a webhook quickly, stores it, and queues an execution. The worker performs the workflow steps and automatically requeues executions left in a stale running state after an interrupted worker:

```text
php /srv/patchbay/cli/worker.php
```

Use the included systemd unit for a continuously running worker. If your host cannot run daemons, execute `php cli/worker.php --once` every minute with cron.

## Retention

Run `php cli/prune.php` daily. The retention period can be changed in Settings. Deleting an old event cascades to its execution and step history.

## Web server

Examples for Nginx and Apache are in `deploy/`. In both cases, the document root must be the `public/` directory.

## Backups

For SQLite, back up `storage/database/patchbay.sqlite` while the application is quiescent or use SQLite's online backup tooling. For MySQL, use your normal database backup process. The `.env` file and whichever encryption key is in use (`APP_KEY` or `storage/app.key`) must be backed up securely; encrypted workflow secrets cannot be decrypted without the same key.
