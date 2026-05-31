<?php

declare(strict_types=1);

namespace MrRijal\MicroService\Http;

final class HealthController
{
    public function __construct(
        private readonly string $serviceName,
        private readonly ?string $queueKey = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $payload = [
            'status' => 'ok',
            'service' => $this->serviceName,
            'timestamp' => gmdate('c'),
        ];

        if ($this->queueKey !== null) {
            $payload['queue'] = $this->queueKey;
        }

        return $payload;
    }
}
