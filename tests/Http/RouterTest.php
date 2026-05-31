<?php

declare(strict_types=1);

namespace MrThito\MicroService\Tests\Http;

use MrThito\MicroService\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function test_it_dispatches_registered_route(): void
    {
        $router = new Router;
        $router->get('/health', static fn (): array => ['status' => 'ok']);

        $response = $router->dispatch('GET', '/health');

        $this->assertSame(['status' => 'ok'], $response);
    }

    public function test_it_returns_not_found_for_missing_route(): void
    {
        $router = new Router;

        $response = $router->dispatch('GET', '/missing');

        $this->assertSame('error', $response['status']);
        $this->assertSame('Route not found.', $response['message']);
    }
}
