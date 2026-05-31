<?php

declare(strict_types=1);

namespace MrThito\MicroService\Support;

use MrThito\MicroService\Contracts\MicroServiceConfig;
use RuntimeException;

final class BaseConfigValidator
{
    public static function validate(MicroServiceConfig $config): void
    {
        if ($config->database()['database'] === '') {
            throw new RuntimeException('DB_DATABASE is required.');
        }

        if ($config->signingSecret() === '') {
            throw new RuntimeException('MICROSERVICE_SIGNING_SECRET is required.');
        }

        if (strlen($config->signingSecret()) < 32) {
            throw new RuntimeException('MICROSERVICE_SIGNING_SECRET must be at least 32 characters.');
        }

        if ($config->requireRedisPassword() && $config->redis()['password'] === null) {
            throw new RuntimeException('REDIS_PASSWORD is required in production mode.');
        }
    }
}
