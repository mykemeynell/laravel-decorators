<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Tests\Unit;

use Attribute;
use MykeMeynell\Laravel\Decorators\Contracts\MethodDecorator;
use MykeMeynell\Laravel\Decorators\DecoratorProxy;
use MykeMeynell\Laravel\Decorators\Tests\TestCase;

final class DecoratorProxyTest extends TestCase
{
    public function test_it_does_call_methods_without_decorators_through_proxy(): void
    {
        $service = new ProxyFixtureService;
        $proxy = new DecoratorProxy($service);

        $this->assertSame('plain:abc', $proxy->plain('abc'));
    }

    public function test_it_does_return_original_object_when_unwrap_is_called(): void
    {
        $service = new ProxyFixtureService;
        $proxy = new DecoratorProxy($service);

        $this->assertSame($service, $proxy->unwrap());
    }

    public function test_it_does_list_only_decorated_methods_when_decorated_methods_is_called(): void
    {
        OrderProbeState::reset();
        $proxy = new DecoratorProxy(new ProxyFixtureService);

        $decorated = $proxy->decoratedMethods();

        $this->assertContains('stacked', $decorated);
        $this->assertContains('decorated', $decorated);
        $this->assertNotContains('plain', $decorated);
        $this->assertNotContains('__construct', $decorated);
        $this->assertNotContains('staticDecorated', $decorated);
    }

    public function test_it_does_proxy_get_set_and_isset_when_target_has_public_property(): void
    {
        $proxy = new DecoratorProxy(new ProxyFixtureService);

        $this->assertTrue(isset($proxy->state));
        $this->assertSame('start', $proxy->state);

        $proxy->state = 'changed';

        $this->assertSame('changed', $proxy->state);
    }

    public function test_it_does_execute_stacked_decorators_in_outermost_first_order_when_declared_top_to_bottom(): void
    {
        OrderProbeState::reset();
        $proxy = new DecoratorProxy(new ProxyFixtureService);

        $result = $proxy->stacked();

        $this->assertSame('ok', $result);
        $this->assertSame(
            ['A:before', 'B:before', 'C:before', 'core', 'C:after', 'B:after', 'A:after'],
            OrderProbeState::$events,
        );
    }

    public function test_it_does_bypass_chain_building_when_decorators_are_disabled(): void
    {
        OrderProbeState::reset();
        $proxy = new DecoratorProxy(new ProxyFixtureService, false);

        $this->assertSame([], $proxy->decoratedMethods());
        $this->assertSame('decorated', $proxy->decorated());
        $this->assertSame([], OrderProbeState::$events);
    }

    public function test_it_throws_logic_exception_when_decorator_returns_non_callable(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must return a callable from wrap()');

        new DecoratorProxy(new BrokenDecoratorService);
    }
}

#[Attribute(Attribute::TARGET_METHOD)]
final class BrokenDecorator implements MethodDecorator
{
    public function wrap(callable $next, array $context = []): callable
    {
        // @phpstan-ignore-next-line
        return 'not-a-callable';
    }
}

final class BrokenDecoratorService
{
    #[BrokenDecorator]
    public function fail(): void {}
}

final class OrderProbeState
{
    /** @var array<int, string> */
    public static array $events = [];

    public static function reset(): void
    {
        self::$events = [];
    }
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class OrderProbe implements MethodDecorator
{
    public function __construct(private readonly string $name) {}

    public function wrap(callable $next, array $context = []): callable
    {
        return function (array $args) use ($next): mixed {
            OrderProbeState::$events[] = $this->name.':before';
            $result = $next($args);
            OrderProbeState::$events[] = $this->name.':after';

            return $result;
        };
    }
}

final class ProxyFixtureService
{
    public string $state = 'start';

    #[OrderProbe('constructor')]
    public function __construct() {}

    public function plain(string $value): string
    {
        return 'plain:'.$value;
    }

    #[OrderProbe('X')]
    public static function staticDecorated(): string
    {
        return 'static';
    }

    #[OrderProbe('D')]
    public function decorated(): string
    {
        return 'decorated';
    }

    #[OrderProbe('A')]
    #[OrderProbe('B')]
    #[OrderProbe('C')]
    public function stacked(): string
    {
        OrderProbeState::$events[] = 'core';

        return 'ok';
    }
}
