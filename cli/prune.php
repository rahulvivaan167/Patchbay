<?php

declare(strict_types=1);

use Patchbay\Bootstrap;
use Patchbay\Database;
use Patchbay\Repositories\EventRepository;
use Patchbay\Repositories\SettingRepository;
use Patchbay\Security\RateLimiter;

require_once dirname(__DIR__) . '/src/Bootstrap.php';
Bootstrap::boot(false);
Database::migrate();

$settings = new SettingRepository();
$days = max(1, (int) $settings->get('retention_days', (string) config('app.retention_days', 30)));
$cutoff = (new DateTimeImmutable('now'))->modify("-{$days} days")->format('Y-m-d H:i:s');
$events = (new EventRepository())->deleteOlderThan($cutoff);
$buckets = (new RateLimiter())->prune();

fwrite(STDOUT, "Pruned {$events} event(s) older than {$days} days and {$buckets} stale rate-limit bucket(s).\n");
