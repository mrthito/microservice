<?php

declare(strict_types=1);

namespace MrThito\MicroService\Support;

use RuntimeException;

final class Manifest
{
    /**
     * @return array{name: string, license: string, scopes: list<string>, health: string}
     */
    public static function load(string $basePath): array
    {
        $path = rtrim($basePath, '/').'/boot.json';

        if (! is_file($path)) {
            throw new RuntimeException('boot.json manifest was not found.');
        }

        $manifest = json_decode((string) file_get_contents($path), true);

        if (! is_array($manifest)) {
            throw new RuntimeException('boot.json manifest is invalid.');
        }

        return [
            'name' => (string) ($manifest['name'] ?? ''),
            'license' => (string) ($manifest['license'] ?? ''),
            'scopes' => array_values(array_map('strval', $manifest['scopes'] ?? [])),
            'health' => (string) ($manifest['health'] ?? '/health'),
        ];
    }
}
