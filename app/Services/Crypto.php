<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use RuntimeException;

/**
 * Authenticated symmetric encryption (AES-256-GCM) used for
 * reversibly storing customer-supplied account passwords that the
 * admin must read back to provision the subscription.
 */
final class Crypto
{
    private const CIPHER = 'aes-256-gcm';

    private static function key(): string
    {
        $key = (string) Config::get('app.key', '');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true) ?: '';
        }
        if (strlen($key) < 32) {
            // Derive a stable 32-byte key from whatever is configured.
            $key = hash('sha256', $key . '|nullik', true);
        } else {
            $key = substr($key, 0, 32);
        }
        return $key;
    }

    public static function encrypt(string $plaintext): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new RuntimeException('Encryption failed.');
        }
        return base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(?string $payload): string
    {
        if ($payload === null || $payload === '') {
            return '';
        }
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 28) {
            return '';
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $plain = openssl_decrypt($cipher, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        return $plain === false ? '' : $plain;
    }
}
