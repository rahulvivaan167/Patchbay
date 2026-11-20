<?php

declare(strict_types=1);

namespace Patchbay\Security;

final class UrlGuard
{
    public function isSafeHttpUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = trim((string) ($parts['host'] ?? ''), '[]');
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') return false;
        if (strtolower($host) === 'localhost' || str_ends_with(strtolower($host), '.localhost')) return false;

        if (filter_var($host, FILTER_VALIDATE_IP)) return $this->isPublicIp($host);

        $ips = [];
        if (function_exists('dns_get_record')) {
            foreach (dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
                if (!empty($record['ip'])) $ips[] = (string) $record['ip'];
                if (!empty($record['ipv6'])) $ips[] = (string) $record['ipv6'];
            }
        }
        if (!$ips) {
            $fallback = gethostbyname($host);
            if ($fallback !== $host) $ips[] = $fallback;
        }
        if (!$ips) return false;
        foreach (array_unique($ips) as $ip) {
            if (!$this->isPublicIp($ip)) return false;
        }
        return true;
    }

    private function isPublicIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
