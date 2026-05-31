<?php

declare(strict_types=1);

namespace MrThito\MicroService\Tests\Support;

use MrThito\MicroService\Contracts\MicroServiceConfig;

final class TestConfig implements MicroServiceConfig
{
    public function __construct(
        private readonly string $serviceName = 'test_service',
        private readonly string $healthPath = '/health',
        private readonly ?string $queueKey = 'microservices:test',
    ) {}

    public function appKey(): string
    {
        return 'base64:'.base64_encode(str_repeat('a', 32));
    }

    public function database(): array
    {
        return [
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'test',
            'username' => 'root',
            'password' => '',
        ];
    }

    public function redis(): array
    {
        return [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'database' => 0,
            'queue_key' => $this->queueKey ?? 'microservices:test',
        ];
    }

    public function signingSecret(): string
    {
        return str_repeat('b', 32);
    }

    public function eventMaxAgeSeconds(): int
    {
        return 900;
    }

    public function requireRedisPassword(): bool
    {
        return false;
    }

    public function logLevel(): string
    {
        return 'info';
    }

    public function serviceName(): string
    {
        return $this->serviceName;
    }

    public function healthPath(): string
    {
        return $this->healthPath;
    }
}
