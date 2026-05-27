<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use MykeMeynell\Laravel\Decorators\DecoratorFactory;
use MykeMeynell\Laravel\Decorators\DecoratorProxy;
use MykeMeynell\Laravel\Decorators\DecoratorServiceProvider;
use MykeMeynell\Laravel\Decorators\Tests\TestCase;

final class DecoratorServiceProviderTest extends TestCase
{
    public function test_it_does_resolve_decorator_alias_to_factory_when_container_uses_decorator_binding(): void
    {
        $resolved = $this->app->make('decorator');

        $this->assertInstanceOf(DecoratorFactory::class, $resolved);
    }

    public function test_it_does_auto_wrap_configured_classes_when_decorate_list_contains_class_names(): void
    {
        $this->app['config']->set('decorators.decorate', [ProviderFixtureService::class]);

        /** @var DecoratorServiceProvider $provider */
        $provider = $this->app->getProvider(DecoratorServiceProvider::class);
        $provider->boot();

        $resolved = $this->app->make(ProviderFixtureService::class);

        $this->assertInstanceOf(DecoratorProxy::class, $resolved);
        $this->assertSame('provider-ok', $resolved->ping());
    }

    public function test_it_does_register_publishable_config_keys_when_booted(): void
    {
        /** @var DecoratorServiceProvider $provider */
        $provider = $this->app->getProvider(DecoratorServiceProvider::class);
        $provider->boot();

        $paths = ServiceProvider::pathsToPublish(DecoratorServiceProvider::class, 'decorators-config');

        $this->assertIsArray($paths);
        $this->assertNotEmpty($paths);

        $source = array_key_first($paths);
        $target = $paths[$source];

        $this->assertStringEndsWith('/config/decorators.php', (string) $source);
        $this->assertStringContainsString('decorators.php', (string) $target);

        $this->assertArrayHasKey('enabled', $this->app['config']->get('decorators'));
        $this->assertArrayHasKey('log_channel', $this->app['config']->get('decorators'));
        $this->assertArrayHasKey('cache_store', $this->app['config']->get('decorators'));
        $this->assertArrayHasKey('cache_prefix', $this->app['config']->get('decorators'));
        $this->assertArrayHasKey('decorate', $this->app['config']->get('decorators'));
    }
}

final class ProviderFixtureService
{
    public function ping(): string
    {
        return 'provider-ok';
    }
}
