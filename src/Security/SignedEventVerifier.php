<?php

declare(strict_types=1);

namespace MrRijal\MicroService\Security;

use MrRijal\MicroService\Contracts\EventVerifier;
use RuntimeException;

final class SignedEventVerifier implements EventVerifier
{
    /**
     * @param  Closure(array<string, mixed>): array<string, mixed>  $payloadValidator
     */
    public function __construct(
        private readonly string $expectedEvent,
        private readonly string $signingSecret,
        private readonly int $eventMaxAgeSeconds,
        private readonly \Closure $payloadValidator,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function verify(array $payload): array
    {
        if (($payload['event'] ?? '') !== $this->expectedEvent) {
            throw new RuntimeException('Unsupported event type.');
        }

        $issuedAt = filter_var($payload['issued_at'] ?? null, FILTER_VALIDATE_INT);

        if ($issuedAt === false || $issuedAt < 1) {
            throw new RuntimeException('Event issued_at timestamp is missing.');
        }

        $age = time() - $issuedAt;

        if ($age > $this->eventMaxAgeSeconds) {
            throw new RuntimeException('Event has expired.');
        }

        if ($age < -60) {
            throw new RuntimeException('Event timestamp is in the future.');
        }

        $signature = (string) ($payload['signature'] ?? '');

        if ($signature === '') {
            throw new RuntimeException('Event signature is missing.');
        }

        $expected = EventSigner::sign($payload, $this->signingSecret);

        if (! hash_equals($expected, $signature)) {
            throw new RuntimeException('Event signature is invalid.');
        }

        return ($this->payloadValidator)($payload);
    }
}
