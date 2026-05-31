# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-05-31

### Added

- Default `/health` and `/` routes on every `Router` instance
- `Router::forConfig()` registers service-aware health responses from `MicroServiceConfig`
- `Http\Server` for one-line HTTP entrypoints (`$server->run()`)
- `MicroServiceConfig::healthPath()` contract method

## [1.0.0] - 2026-05-31

### Added

- Environment file loader (`Support\Env`)
- Secure stdout logger with sensitive field redaction (`Support\Logger`)
- Service manifest reader for `boot.json` (`Support\Manifest`)
- Base configuration validator (`Support\BaseConfigValidator`)
- PDO MySQL connection helper (`Database\Connection`)
- Laravel `APP_KEY` payload decryption (`Encryption\LaravelDecryptor`)
- Minimal Redis RESP client with BRPOP support (`Queue\RedisClient`)
- Redis queue listener with signed event verification (`Queue\RedisQueueListener`)
- HMAC-SHA256 event signing and verification (`Security\EventSigner`, `Security\SignedEventVerifier`)
- Minimal HTTP router and health controller (`Http\Router`, `Http\HealthController`)
- Contracts for config, event processors, and event verifiers (`Contracts\*`)
- Application bootstrap helper (`Bootstrap`)

[1.0.0]: https://github.com/mrthito/microservice/releases/tag/v1.0.0
