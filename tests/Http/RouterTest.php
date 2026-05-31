<?php

declare(strict_types=1);

namespace MrThito\MicroService\Tests\Http;

use MrThito\MicroService\Http\Router;
use MrThito\MicroService\Http\Server;
use MrThito\MicroService\Tests\Support\TestConfig;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function test_it_includes_default_health_route(): void
    {
        $router = new Router;

        $response = $router->dispatch('GET', '/health');

        $this->assertSame('ok', $response['status']);
    }

    public function test_it_includes_default_root_health_route(): void
    {
        $router = new Router;

        $response = $router->dispatch('GET', '/');

        $this->assertSame('ok', $response['status']);
    }

    public function test_for_config_registers_service_health_route(): void
    {
        $router = Router::forConfig(new TestConfig(
            serviceName: 'orders_service',
            queueKey: 'microservices:orders',
        ));

        $response = $router->dispatch('GET', '/health');

        $this->assertSame('ok', $response['status']);
        $this->assertSame('orders_service', $response['service']);
        $this->assertSame('microservices:orders', $response['queue']);
    }

    public function test_it_dispatches_custom_registered_route(): void
    {
        $router = new Router;
        $router->get('/metrics', static fn (): array => ['metrics' => true]);

        $response = $router->dispatch('GET', '/metrics');

        $this->assertSame(['metrics' => true], $response);
    }

    public function test_it_returns_not_found_for_missing_route(): void
    {
        $router = new Router;

        $response = $router->dispatch('GET', '/missing');

        $this->assertSame('error', $response['status']);
        $this->assertSame('Route not found.', $response['message']);
    }
}

final class ServerTest extends TestCase
{
    public function test_it_handles_health_request(): void
    {
        $server = new Server(new TestConfig(serviceName: 'billing_service'));

        $response = $server->handle('GET', '/health');

        $this->assertSame('ok', $response['status']);
        $this->assertSame('billing_service', $response['service']);
    }
}
