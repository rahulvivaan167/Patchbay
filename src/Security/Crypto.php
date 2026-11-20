<?php

declare(strict_types=1);

namespace Patchbay\Security;

use RuntimeException;

final class Crypto
{
    public static function assertKeyIsUsable(string $key): void
    {
        if (strlen($key) < 24) throw new RuntimeException('APP_KEY must be at least 24 characters.');
    }

    public static function encrypt(string $plaintext): string
    {
        if ($plaintext === '') return '';
        $key = self::key();
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) throw new RuntimeException('Unable to encrypt value.');
        return base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(string $encoded): string
    {
        if ($encoded === '') return '';
        $blob = base64_decode($encoded, true);
        if ($blob === false || strlen($blob) < 28) return '';
        $iv = substr($blob, 0, 12);
        $tag = substr($blob, 12, 16);
        $cipher = substr($blob, 28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        return $plain === false ? '' : $plain;
    }

    private static function key(): string
    {
        $source = (string) config('app.key', '');
        self::assertKeyIsUsable($source);
        return hash('sha256', $source, true);
    }
}
