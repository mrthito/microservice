<?php

declare(strict_types=1);

namespace MrRijal\MicroService;

use MrRijal\MicroService\Contracts\MicroServiceConfig;
use MrRijal\MicroService\Support\Env;

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
