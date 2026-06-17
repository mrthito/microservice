<?php

declare(strict_types=1);

namespace MrThito\MicroService\Queue;

use RuntimeException;

/**
 * Minimal Redis client (RESP) — supports AUTH, SELECT, and BRPOP only.
 */
final class RedisClient
{
    /** @var resource|null */
    private $socket = null;

    /**
     * @param  array{host: string, port: int, password: ?string, database: int}  $redis
     */
    public function __construct(private readonly array $redis) {}

    /**
     * @return array{0: string, 1: string}|null
     */
    public function brpop(string $key, int $timeoutSeconds): ?array
    {
        $this->connect();

        $this->command(['BRPOP', $key, (string) $timeoutSeconds]);
        $response = $this->readResponse();

        if ($response === null) {
            return null;
        }

        if (! is_array($response) || count($response) !== 2) {
            throw new RuntimeException('Unexpected Redis BRPOP response.');
        }

        return [(string) $response[0], (string) $response[1]];
    }

    public function rpush(string $key, string $value): void
    {
        $this->connect();
        $this->command(['RPUSH', $key, $value]);
        $this->readResponse();
    }

    public function del(string $key): void
    {
        $this->connect();
        $this->command(['DEL', $key]);
        $this->readResponse();
    }

    public function resetConnection(): void
    {
        $this->close();
    }

    public function close(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        $this->socket = null;
    }

    private function connect(): void
    {
        if (is_resource($this->socket)) {
            return;
        }

        $address = sprintf('tcp://%s:%d', $this->redis['host'], $this->redis['port']);
        $socket = @stream_socket_client($address, $errno, $errstr, 5);

        if ($socket === false) {
            throw new RuntimeException("Redis connection failed: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, 0, 0);
        $this->socket = $socket;

        if ($this->redis['password'] !== null) {
            $this->command(['AUTH', $this->redis['password']]);
            $this->expectOk($this->readResponse());
        }

        if (($this->redis['database'] ?? 0) > 0) {
            $this->command(['SELECT', (string) $this->redis['database']]);
            $this->expectOk($this->readResponse());
        }
    }

    /**
     * @param  list<string>  $parts
     */
    private function command(array $parts): void
    {
        $payload = '*'.count($parts)."\r\n";

        foreach ($parts as $part) {
            $payload .= '$'.strlen($part)."\r\n".$part."\r\n";
        }

        $written = fwrite($this->socket, $payload);

        if ($written === false) {
            throw new RuntimeException('Failed writing to Redis socket.');
        }
    }

    private function readResponse(): mixed
    {
        $line = fgets($this->socket);

        if ($line === false) {
            throw new RuntimeException('Redis connection closed unexpectedly.');
        }

        $type = $line[0];
        $payload = rtrim(substr($line, 1), "\r\n");

        return match ($type) {
            '+' => $payload,
            '-' => throw new RuntimeException('Redis error: '.$payload),
            ':' => (int) $payload,
            '$' => $this->readBulk((int) $payload),
            '*' => $this->readArray((int) $payload),
            default => throw new RuntimeException('Unknown Redis response type.'),
        };
    }

    private function readBulk(int $length): ?string
    {
        if ($length === -1) {
            return null;
        }

        $data = stream_get_contents($this->socket, $length + 2);

        if ($data === false || strlen($data) < $length) {
            throw new RuntimeException('Failed reading Redis bulk string.');
        }

        return substr($data, 0, $length);
    }

    /**
     * @return list<mixed>
     */
    private function readArray(int $count): array
    {
        if ($count === -1) {
            return [];
        }

        $items = [];

        for ($i = 0; $i < $count; $i++) {
            $items[] = $this->readResponse();
        }

        return $items;
    }

    private function expectOk(mixed $response): void
    {
        if ($response !== 'OK') {
            throw new RuntimeException('Unexpected Redis response: '.json_encode($response));
        }
    }
}
