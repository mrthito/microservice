<?php

declare(strict_types=1);

namespace MrThito\MicroService;

use MrThito\MicroService\Contracts\MicroServiceConfig;
use MrThito\MicroService\Support\Env;

final class Bootstrap
{
    private static bool $booted = false;

    /**
     * @param  callable(): MicroServiceConfig  $configFactory
     */
    public static function boot(string $basePath, callable $configFactory): MicroServiceConfig
    {
        if (! self::$booted) {
            Env::load($basePath.'/.env');
            self::$booted = true;
        }

        return $configFactory();
    }

    public static function reset(): void
    {
        self::$booted = false;
    }
}
