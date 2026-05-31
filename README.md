# mr-rijal/microservice

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mr-rijal/microservice.svg?style=flat-square)](https://packagist.org/packages/mr-rijal/microservice)
[![Total Downloads](https://img.shields.io/packagist/dt/mr-rijal/microservice.svg?style=flat-square)](https://packagist.org/packages/mr-rijal/microservice)
[![License](https://img.shields.io/packagist/l/mr-rijal/microservice.svg?style=flat-square)](LICENSE)

Lightweight PHP foundation for event-driven microservices that share a Laravel database and Redis queue — with zero framework dependencies.

## Requirements

- PHP 8.4+
- Extensions: `json`, `openssl`, `pdo`

## Installation

```bash
composer require mr-rijal/microservice
```

Local development with a path repository:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../package-ms"
        }
    ],
    "require": {
        "mr-rijal/microservice": "@dev"
    }
}
```

## Features

- `.env` loading without external dependencies
- Secure stdout logging with sensitive field redaction
- PDO MySQL connection helper
- Laravel `APP_KEY` payload decryption
- Minimal Redis RESP client (BRPOP)
- HMAC-SHA256 signed queue event verification
- Minimal HTTP router for health checks
- `boot.json` service manifest reader

## Quick start

### 1. Implement service config

```php
use MrRijal\MicroService\Contracts\MicroServiceConfig;
use MrRijal\MicroService\Support\Env;
use MrRijal\MicroService\Support\Manifest;

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
use MrRijal\MicroService\Bootstrap;
use MrRijal\MicroService\Queue\RedisQueueListener;
use MrRijal\MicroService\Security\SignedEventVerifier;
use MrRijal\MicroService\Support\BaseConfigValidator;
use MrRijal\MicroService\Support\Logger;

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

### 3. Expose a health endpoint

```php
use MrRijal\MicroService\Http\HealthController;
use MrRijal\MicroService\Http\Router;

$router = new Router;
$router->get('/health', new HealthController(
    serviceName: $config->serviceName(),
    queueKey: $config->redis()['queue_key'],
));

echo json_encode($router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']));
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

Load it with `Manifest::load($basePath)`.

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

1. Push this repository to GitHub (for example `github.com/mr-rijal/microservice`).
2. Create a release tag: `git tag v1.0.0 && git push origin v1.0.0`.
3. Submit the repository URL at [packagist.org](https://packagist.org/packages/submit).
4. Enable the Packagist GitHub hook for automatic updates on new tags.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
