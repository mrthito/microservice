# mrthito/microservice

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mrthito/microservice.svg?style=flat-square)](https://packagist.org/packages/mrthito/microservice)
[![Total Downloads](https://img.shields.io/packagist/dt/mrthito/microservice.svg?style=flat-square)](https://packagist.org/packages/mrthito/microservice)
[![License](https://img.shields.io/packagist/l/mrthito/microservice.svg?style=flat-square)](LICENSE)

Lightweight PHP foundation for event-driven microservices that share a Laravel database and Redis queue — with zero framework dependencies.

## Requirements

- PHP 8.4+
- Extensions: `json`, `openssl`, `pdo`

## Installation

```bash
composer require mrthito/microservice
```

## Features

- `.env` loading without external dependencies
- Secure stdout logging with sensitive field redaction
- PDO MySQL connection helper
- Laravel `APP_KEY` payload decryption
- Minimal Redis RESP client (BRPOP)
- HMAC-SHA256 signed queue event verification
- Minimal HTTP router with **built-in `/health` route by default**
- `Http\Server` for one-line HTTP entrypoints
- `boot.json` service manifest reader

## Quick start

### 1. Implement service config

```php
use MrThito\MicroService\Contracts\MicroServiceConfig;
use MrThito\MicroService\Support\Env;
use MrThito\MicroService\Support\Manifest;

final readonly class Config implements MicroServiceConfig
{
    public static function fromEnvironment(string $basePath): self
    {
        $manifest = Manifest::load($basePath);

        return new self(
            serviceName: $manifest['name'],
            // ... map Env::getString(), Env::getInt(), etc.
        );
    }

    // Implement MicroServiceConfig methods...
}
```

### 2. Wire the queue worker

```php
use MrThito\MicroService\Bootstrap;
use MrThito\MicroService\Queue\RedisQueueListener;
use MrThito\MicroService\Security\SignedEventVerifier;
use MrThito\MicroService\Support\BaseConfigValidator;
use MrThito\MicroService\Support\Logger;

$config = Bootstrap::boot('/path/to/service', fn () => Config::fromEnvironment('/path/to/service'));
BaseConfigValidator::validate($config);

$logger = new Logger($config->logLevel());

$verifier = new SignedEventVerifier(
    expectedEvent: 'order.created',
    signingSecret: $config->signingSecret(),
    eventMaxAgeSeconds: $config->eventMaxAgeSeconds(),
    payloadValidator: static fn (array $payload): array => [
        'order_id' => (int) $payload['order_id'],
    ],
);

$listener = new RedisQueueListener(
    redisConfig: $config->redis(),
    verifier: $verifier,
    processor: $orderProcessor,
    logger: $logger,
);

$listener->listen();
```

### 3. Expose the HTTP server

Health routes (`/health` and `/`) are registered automatically.

```php
use MrThito\MicroService\Http\Server;

$config = Bootstrap::boot('/path/to/service', fn () => Config::fromEnvironment('/path/to/service'));

(new Server($config))->run();
```

Add custom routes before serving:

```php
$server = new Server($config);
$server->router()->get('/metrics', static fn (): array => ['uptime' => time()]);

$server->run();
```

## Service manifest

Each microservice should include a `boot.json` file in its project root:

```json
{
    "name": "my_microservice",
    "license": "MIT",
    "scopes": ["orders.process"],
    "health": "/health"
}
```

Load it with `Manifest::load($basePath)`. The `health` path is exposed via `MicroServiceConfig::healthPath()` and used by the default router.

## Security

- Sign queue payloads with `EventSigner::sign($payload, $secret)` before publishing.
- Verify events with `SignedEventVerifier` before processing.
- Set `REQUIRE_REDIS_PASSWORD=true` in production when Redis uses AUTH.
- Use signing secrets of at least 32 characters.

## Testing

```bash
composer test
```

## Publishing to Packagist

1. Push this repository to GitHub (for example `github.com/mrthito/microservice`).
2. Create a release tag: `git tag v1.0.0 && git push origin v1.0.0`.
3. Submit the repository URL at [packagist.org](https://packagist.org/packages/submit).
4. Enable the Packagist GitHub hook for automatic updates on new tags.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
