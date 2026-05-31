<?php

declare(strict_types=1);

namespace MrRijal\MicroService\Queue;

use MrRijal\MicroService\Contracts\EventProcessor;
use MrRijal\MicroService\Contracts\EventVerifier;
use MrRijal\MicroService\Support\Logger;
use Throwable;

final class RedisQueueListener
{
    private ?RedisClient $client = null;

    /**
     * @param  array{host: string, port: int, password: ?string, database: int, queue_key: string}  $redisConfig
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

        $this->logger->info('Listening for queue events.', [
            'queue' => $queueKey,
        ]);

        while (true) {
            try {
                $result = $this->client()->brpop($queueKey, $timeoutSeconds);

                if ($result === null) {
                    continue;
                }

                $payload = json_decode($result[1], true);

                if (! is_array($payload)) {
                    $this->logger->warning('Ignored invalid queue payload.', [
                        'payload_length' => strlen($result[1]),
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

                $this->processor->process($verified);
            } catch (Throwable $exception) {
                $this->logger->error('Queue processing error.', [
                    'error' => $exception->getMessage(),
                ]);

                sleep(1);
            }
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
