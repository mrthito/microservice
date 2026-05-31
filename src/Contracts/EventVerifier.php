<?php

declare(strict_types=1);

namespace MrThito\MicroService\Contracts;

interface EventVerifier
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function verify(array $payload): array;
}
