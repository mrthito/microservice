<?php

declare(strict_types=1);

namespace MrThito\MicroService\Security;

final class EventSigner
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function sign(array $payload, string $secret): string
    {
        unset($payload['signature']);
        ksort($payload);

        return hash_hmac(
            'sha256',
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            $secret,
        );
    }
}
