<?php

declare(strict_types=1);

namespace MrRijal\MicroService\Tests\Security;

use MrRijal\MicroService\Security\EventSigner;
use MrRijal\MicroService\Security\SignedEventVerifier;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SignedEventVerifierTest extends TestCase
{
    public function test_it_verifies_valid_signed_event(): void
    {
        $secret = str_repeat('a', 32);

        $payload = [
            'event' => 'order.created',
            'order_id' => 10,
            'issued_at' => time(),
        ];

        $payload['signature'] = EventSigner::sign($payload, $secret);

        $verifier = new SignedEventVerifier(
            expectedEvent: 'order.created',
            signingSecret: $secret,
            eventMaxAgeSeconds: 900,
            payloadValidator: static fn (array $payload): array => [
                'order_id' => (int) $payload['order_id'],
            ],
        );

        $this->assertSame(['order_id' => 10], $verifier->verify($payload));
    }

    public function test_it_rejects_invalid_signature(): void
    {
        $verifier = new SignedEventVerifier(
            expectedEvent: 'order.created',
            signingSecret: str_repeat('a', 32),
            eventMaxAgeSeconds: 900,
            payloadValidator: static fn (array $payload): array => $payload,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Event signature is invalid.');

        $verifier->verify([
            'event' => 'order.created',
            'issued_at' => time(),
            'signature' => 'invalid',
        ]);
    }

    public function test_it_rejects_expired_event(): void
    {
        $secret = str_repeat('b', 32);

        $payload = [
            'event' => 'order.created',
            'issued_at' => time() - 1000,
        ];
        $payload['signature'] = EventSigner::sign($payload, $secret);

        $verifier = new SignedEventVerifier(
            expectedEvent: 'order.created',
            signingSecret: $secret,
            eventMaxAgeSeconds: 60,
            payloadValidator: static fn (array $payload): array => $payload,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Event has expired.');

        $verifier->verify($payload);
    }
}
