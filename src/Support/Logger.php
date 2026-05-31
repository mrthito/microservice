<?php

declare(strict_types=1);

namespace MrRijal\MicroService\Support;

final class Logger
{
    /** @var list<string> */
    private const REDACTED_KEYS = [
        'account_number',
        'account_number_plain',
        'password',
        'secret',
        'signature',
        'ifsc_code',
        'payload',
    ];

    public function __construct(private readonly string $minLevel = 'info') {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function log(string $level, string $message, array $context): void
    {
        if (! $this->shouldLog($level)) {
            return;
        }

        $line = sprintf(
            '[%s] %s: %s%s',
            date('c'),
            strtoupper($level),
            $message,
            $context === [] ? '' : ' '.json_encode($this->redact($context), JSON_UNESCAPED_SLASHES),
        );

        fwrite(STDOUT, $line.PHP_EOL);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function redact(array $context): array
    {
        $redacted = [];

        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), self::REDACTED_KEYS, true)) {
                $redacted[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $redacted[$key] = $this->redact($value);

                continue;
            }

            $redacted[$key] = $value;
        }

        return $redacted;
    }

    private function shouldLog(string $level): bool
    {
        $order = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
        $min = $order[strtolower($this->minLevel)] ?? 1;
        $current = $order[$level] ?? 1;

        return $current >= $min;
    }
}
