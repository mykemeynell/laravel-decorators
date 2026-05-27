<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Tests;

use Mockery;
use MykeMeynell\Laravel\Decorators\DecoratorServiceProvider;
use MykeMeynell\Laravel\Decorators\Facades\Decorator;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            DecoratorServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return ['Decorator' => Decorator::class];
    }

    protected function defineEnvironment($app): void
    {
        $config = require __DIR__.'/../config/decorators.php';

        $app['config']->set('decorators', $config);
        $app['config']->set('app.env', 'testing');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
