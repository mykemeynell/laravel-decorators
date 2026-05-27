<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Tests\Unit;

use Attribute;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use MykeMeynell\Laravel\Decorators\Contracts\MethodDecorator;
use MykeMeynell\Laravel\Decorators\DecoratorFactory;
use MykeMeynell\Laravel\Decorators\DecoratorProxy;
use MykeMeynell\Laravel\Decorators\Tests\TestCase;

final class DecoratorFactoryTest extends TestCase
{
    public function test_it_does_resolve_dependencies_through_container_when_make_is_called(): void
    {
        $factory = $this->app->make(DecoratorFactory::class);

        $proxy = $factory->make(FactoryService::class, ['seed' => 'abc']);

        $this->assertInstanceOf(DecoratorProxy::class, $proxy);
        $this->assertSame('dep-abc', $proxy->dependencyValue());
    }

    public function test_it_does_return_decorator_proxy_when_wrap_is_called(): void
    {
        $factory = $this->app->make(DecoratorFactory::class);

        $proxy = $factory->wrap(new FactoryService(new FactoryDependency, 'xyz'));

        $this->assertInstanceOf(DecoratorProxy::class, $proxy);
        $this->assertSame('dep-xyz', $proxy->dependencyValue());
    }

    public function test_it_does_bind_proxy_back_into_container_when_singleton_is_called(): void
    {
        $factory = $this->app->make(DecoratorFactory::class);

        $proxy = $factory->singleton(FactoryService::class, ['seed' => 'singleton']);
        $resolved = $this->app->make(FactoryService::class);

        $this->assertSame($proxy, $resolved);
        $this->assertInstanceOf(DecoratorProxy::class, $resolved);
    }

    public function test_it_does_bypass_all_chains_when_decorators_are_disabled(): void
    {
        FactoryProbeState::reset();
        $this->app['config']->set('decorators.enabled', false);

        $factory = $this->app->make(DecoratorFactory::class);
        $proxy = $factory->wrap(new FactoryDecoratedService);

        $this->assertSame([], $proxy->decoratedMethods());
        $this->assertSame('done', $proxy->work());
        $this->assertSame([], FactoryProbeState::$events);
    }

    public function test_it_does_read_enabled_flag_from_container_config_when_repository_is_not_constructor_injected(): void
    {
        $container = new Container;
        $container->instance('config', new Repository(['decorators' => ['enabled' => false]]));

        $factory = new DecoratorFactory($container);
        $proxy = $factory->wrap(new FactoryDecoratedService);

        $this->assertSame([], $proxy->decoratedMethods());
    }

    public function test_it_does_default_to_enabled_when_container_has_no_config_binding(): void
    {
        FactoryProbeState::reset();
        $factory = new DecoratorFactory(new Container);
        $proxy = $factory->wrap(new FactoryDecoratedService);

        $this->assertContains('work', $proxy->decoratedMethods());
        $proxy->work();
        $this->assertSame(['decorator-ran'], FactoryProbeState::$events);
    }
}

final class FactoryDependency
{
    public function value(string $seed): string
    {
        return 'dep-'.$seed;
    }
}

final class FactoryService
{
    public function __construct(
        private readonly FactoryDependency $dependency,
        private readonly string $seed = 'default',
    ) {}

    public function dependencyValue(): string
    {
        return $this->dependency->value($this->seed);
    }
}

final class FactoryProbeState
{
    /** @var array<int, string> */
    public static array $events = [];

    public static function reset(): void
    {
        self::$events = [];
    }
}

#[Attribute(Attribute::TARGET_METHOD)]
final class FactoryProbeDecorator implements MethodDecorator
{
    public function wrap(callable $next, array $context = []): callable
    {
        return function (array $args) use ($next): mixed {
            FactoryProbeState::$events[] = 'decorator-ran';

            return $next($args);
        };
    }
}

final class FactoryDecoratedService
{
    #[FactoryProbeDecorator]
    public function work(): string
    {
        return 'done';
    }
}
