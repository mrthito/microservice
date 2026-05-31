<?php

declare(strict_types=1);

namespace MrThito\MicroService\Tests\Support;

use MrThito\MicroService\Support\Env;
use PHPUnit\Framework\TestCase;

final class EnvTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('MS_TEST_KEY');
        unset($_ENV['MS_TEST_KEY']);
    }

    public function test_it_loads_env_file_values(): void
    {
        $path = sys_get_temp_dir().'/microservice-env-'.uniqid('', true).'.env';
        file_put_contents($path, "MS_TEST_KEY=hello\n# comment\nMS_IGNORED\n");

        Env::load($path);

        $this->assertSame('hello', Env::getString('MS_TEST_KEY'));

        unlink($path);
    }

    public function test_it_parses_quoted_values(): void
    {
        $path = sys_get_temp_dir().'/microservice-env-'.uniqid('', true).'.env';
        file_put_contents($path, 'MS_TEST_KEY="quoted-value"'."\n");

        Env::load($path);

        $this->assertSame('quoted-value', Env::getString('MS_TEST_KEY'));

        unlink($path);
    }
}
