<?php

declare(strict_types=1);

namespace MrRijal\MicroService\Contracts;

interface MicroServiceConfig
{
    public function appKey(): string;

    /**
     * @return array{host: string, port: string, database: string, username: string, password: string}
     */
    public function database(): array;

    /**
     * @return array{host: string, port: int, password: ?string, database: int, queue_key: string}
     */
    public function redis(): array;

    public function signingSecret(): string;

    public function eventMaxAgeSeconds(): int;

    public function requireRedisPassword(): bool;

    public function logLevel(): string;

    public function serviceName(): string;
}
