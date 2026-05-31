<?php

declare(strict_types=1);

namespace MrThito\MicroService\Http;

use MrThito\MicroService\Contracts\MicroServiceConfig;
use Throwable;

final class Server
{
    private readonly Router $router;

    public function __construct(MicroServiceConfig $config, ?Router $router = null)
    {
        $this->router = $router ?? Router::forConfig($config);
    }

    public function router(): Router
    {
        return $this->router;
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(string $method, string $path): array
    {
        return $this->router->dispatch($method, $path);
    }

    public function run(): void
    {
        header('Content-Type: application/json');

        try {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

            echo json_encode(
                $this->handle($_SERVER['REQUEST_METHOD'] ?? 'GET', $path),
                JSON_THROW_ON_ERROR,
            );
        } catch (Throwable $exception) {
            http_response_code(503);

            echo json_encode([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], JSON_THROW_ON_ERROR);
        }
    }
}
