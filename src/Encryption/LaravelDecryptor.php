<?php

declare(strict_types=1);

namespace MrThito\MicroService\Encryption;

use RuntimeException;

final class LaravelDecryptor
{
    public function __construct(private readonly string $appKey) {}

    public function decrypt(?string $payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        if (! str_starts_with($payload, 'eyJ') && ! str_contains($payload, '{')) {
            return $payload;
        }

        $key = $this->resolveKey();
        $json = json_decode(base64_decode($payload, true) ?: '', true);

        if (! is_array($json) || ! isset($json['iv'], $json['value'], $json['mac'])) {
            throw new RuntimeException('Invalid encrypted payload format.');
        }

        $mac = hash_hmac('sha256', $json['iv'].$json['value'], $key);

        if (! hash_equals($mac, (string) $json['mac'])) {
            throw new RuntimeException('Encrypted payload MAC is invalid.');
        }

        $iv = base64_decode((string) $json['iv'], true);
        $value = base64_decode((string) $json['value'], true);

        if ($iv === false || $value === false) {
            throw new RuntimeException('Unable to decode encrypted payload.');
        }

        $decrypted = openssl_decrypt($value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new RuntimeException('Unable to decrypt payload. Ensure APP_KEY matches the Laravel server.');
        }

        return $decrypted;
    }

    private function resolveKey(): string
    {
        if ($this->appKey === '') {
            throw new RuntimeException('APP_KEY is not configured.');
        }

        $key = $this->appKey;

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);
        }

        if ($key === false || $key === '') {
            throw new RuntimeException('APP_KEY is invalid.');
        }

        return $key;
    }
}
