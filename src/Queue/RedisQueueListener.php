<?php

declare(strict_types=1);

namespace MrThito\MicroService\Queue;

use MrThito\MicroService\Contracts\EventProcessor;
use MrThito\MicroService\Contracts\EventVerifier;
use MrThito\MicroService\Support\Logger;
use Throwable;

final class RedisQueueListener
{
    private const DEFAULT_MAX_ATTEMPTS = 5;

    private ?RedisClient $client = null;

    /**
     * @param  array{host: string, port: int, password: ?string, database: int, queue_key: string, max_attempts?: int}  $redisConfig
     */
    public function __construct(
        private readonly array $redisConfig,
        private readonly EventVerifier $verifier,
        private readonly EventProcessor $processor,
        private readonly Logger $logger,
    ) {}

    public function listen(int $timeoutSeconds = 5): void
    {
        $queueKey = $this->redisConfig['queue_key'];
        $deadLetterKey = $queueKey.':failed';
        $maxAttempts = max(1, (int) ($this->redisConfig['max_attempts'] ?? self::DEFAULT_MAX_ATTEMPTS));

        $this->logger->info('Listening for queue events.', [
            'queue' => $queueKey,
            'dead_letter_queue' => $deadLetterKey,
            'max_attempts' => $maxAttempts,
        ]);

        while (true) {
            try {
                $result = $this->client()->brpop($queueKey, $timeoutSeconds);

                if ($result === null) {
                    continue;
                }

                $rawPayload = $result[1];
                $payload = json_decode($rawPayload, true);

                if (! is_array($payload)) {
                    $this->logger->warning('Ignored invalid queue payload.', [
                        'payload_length' => strlen($rawPayload),
                    ]);

                    continue;
                }

                try {
                    $verified = $this->verifier->verify($payload);
                } catch (Throwable $exception) {
                    $this->logger->warning('Rejected unsigned or invalid queue event.', [
                        'error' => $exception->getMessage(),
                    ]);

                    continue;
                }

                try {
                    $this->processor->process($verified);
                } catch (Throwable $exception) {
                    $attempts = (int) ($payload['_attempts'] ?? 0) + 1;
                    $permanent = $exception instanceof PermanentProcessingException;

                    if ($permanent || $attempts >= $maxAttempts) {
                        $this->moveToDeadLetter($deadLetterKey, $rawPayload, $payload, $exception, $attempts);

                        continue;
                    }

                    $this->logger->error('Queue event processing failed; requeueing.', [
                        'error' => $exception->getMessage(),
                        'attempt' => $attempts,
                        'max_attempts' => $maxAttempts,
                    ]);

                    $payload['_attempts'] = $attempts;

                    try {
                        $this->client()->rpush($queueKey, json_encode($payload, JSON_THROW_ON_ERROR));
                    } catch (Throwable $requeueException) {
                        $this->logger->error('Failed to requeue event after processing error.', [
                            'error' => $requeueException->getMessage(),
                        ]);
                    }
                }
            } catch (Throwable $exception) {
                $this->logger->error('Queue processing error.', [
                    'error' => $exception->getMessage(),
                ]);

                $this->client()?->resetConnection();
                $this->client = null;

                sleep(1);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function moveToDeadLetter(
        string $deadLetterKey,
        string $rawPayload,
        array $payload,
        Throwable $exception,
        int $attempts,
    ): void {
        $this->logger->error('Queue event moved to dead-letter queue.', [
            'error' => $exception->getMessage(),
            'attempt' => $attempts,
            'dead_letter_queue' => $deadLetterKey,
            'idempotency_key' => $payload['idempotency_key'] ?? null,
        ]);

        $envelope = json_encode([
            'payload' => $payload,
            'raw_payload' => $rawPayload,
            'error' => $exception->getMessage(),
            'attempts' => $attempts,
            'failed_at' => time(),
        ], JSON_THROW_ON_ERROR);

        try {
            $this->client()->rpush($deadLetterKey, $envelope);
        } catch (Throwable $deadLetterException) {
            $this->logger->error('Failed to move event to dead-letter queue.', [
                'error' => $deadLetterException->getMessage(),
            ]);
        }
    }

    private function client(): RedisClient
    {
        if ($this->client instanceof RedisClient) {
            return $this->client;
        }

        $this->client = new RedisClient($this->redisConfig);

        return $this->client;
    }
}
