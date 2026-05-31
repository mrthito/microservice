<?php

declare(strict_types=1);

namespace MrThito\MicroService\Support;

final class Env
{
    public static function load(string $path): void
    {
        if (! is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (! str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $quote = $value[0];
                if (str_ends_with($value, $quote) && strlen($value) >= 2) {
                    $value = substr($value, 1, -1);
                }
            }

            if ($name !== '' && ! array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }

    public static function getString(string $key, string $default = ''): string
    {
        return (string) ($_ENV[$key] ?? $default);
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) ($_ENV[$key] ?? $default);
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        return filter_var($_ENV[$key] ?? $default, FILTER_VALIDATE_BOOL);
    }

    public static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 'null') {
            return null;
        }

        return (string) $value;
    }
}
