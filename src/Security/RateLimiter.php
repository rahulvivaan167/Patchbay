<?php

declare(strict_types=1);

namespace Patchbay\Security;

use Patchbay\Database;

final class RateLimiter
{
    public function hit(string $key, int $limit, int $windowSeconds = 60): bool
    {
        $pdo = Database::connection();
        $bucket = hash('sha256', $key);
        $window = intdiv(time(), $windowSeconds) * $windowSeconds;
        $driver = (string) config('database.driver', 'sqlite');

        if ($driver === 'mysql') {
            $stmt = $pdo->prepare('INSERT INTO rate_limits (bucket, window_start, hits) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE hits = hits + 1');
        } else {
            $stmt = $pdo->prepare('INSERT INTO rate_limits (bucket, window_start, hits) VALUES (?, ?, 1) ON CONFLICT(bucket, window_start) DO UPDATE SET hits = hits + 1');
        }
        $stmt->execute([$bucket, $window]);

        $check = $pdo->prepare('SELECT hits FROM rate_limits WHERE bucket = ? AND window_start = ?');
        $check->execute([$bucket, $window]);
        return (int) $check->fetchColumn() <= max(1, $limit);
    }

    public function prune(): int
    {
        $stmt = Database::connection()->prepare('DELETE FROM rate_limits WHERE window_start < ?');
        $stmt->execute([time() - 86400]);
        return $stmt->rowCount();
    }
}
