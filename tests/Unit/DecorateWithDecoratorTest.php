<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Tests\Unit;

use InvalidArgumentException;
use MykeMeynell\Laravel\Decorators\DecoratorProxy;
use MykeMeynell\Laravel\Decorators\Decorators\DecorateWith;
use MykeMeynell\Laravel\Decorators\Tests\TestCase;

function decorate_with_global_wrapper(callable $next): callable
{
    return function (array $args) use ($next): mixed {
        DecorateWithState::$events[] = 'global:before';
        $result = $next($args);
        DecorateWithState::$events[] = 'global:after';

        return $result;
    };
}

final class DecorateWithDecoratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DecorateWithState::reset();
        $this->app->instance(DecorateWithRecorder::class, new DecorateWithRecorder);
    }

    public function test_it_does_wrap_with_global_function_when_function_name_is_configured(): void
    {
        $proxy = new DecoratorProxy(new DecorateWithValidFixtureService);

        $result = $proxy->viaGlobal('A');

        $this->assertSame('global:A', $result);
        $this->assertSame(['global:before', 'core:global', 'global:after'], DecorateWithState::$events);
    }

    public function test_it_does_wrap_with_static_method_when_class_method_string_points_to_static_callable(): void
    {
        $proxy = new DecoratorProxy(new DecorateWithValidFixtureService);

        $result = $proxy->viaStatic('B');

        $this->assertSame('static:B', $result);
        $this->assertSame(['static:before', 'core:static', 'static:after'], DecorateWithState::$events);
    }

    public function test_it_does_wrap_with_instance_method_when_class_method_string_points_to_non_static_callable(): void
    {
        $proxy = new DecoratorProxy(new DecorateWithValidFixtureService);

        $result = $proxy->viaInstanceString('C');

        $this->assertSame('instance-string:C', $result);
        $this->assertSame(['instance:before', 'core:instance-string', 'instance:after'], DecorateWithState::$events);
    }

    public function test_it_does_wrap_with_invokable_class_when_class_name_is_configured(): void
    {
        $proxy = new DecoratorProxy(new DecorateWithValidFixtureService);

        $result = $proxy->viaInvokable('D');

        $this->assertSame('invokable:D', $result);
        $this->assertSame(['recorder:before', 'core:invokable', 'recorder:after'], DecorateWithState::$events);
    }

    public function test_it_does_throw_invalid_argument_exception_when_resolved_callable_returns_non_callable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must return a callable');

        new DecoratorProxy(new DecorateWithBadReturnFixtureService);
    }

    public function test_it_does_throw_invalid_argument_exception_when_class_method_string_is_unresolvable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot resolve');

        new DecoratorProxy(new DecorateWithMissingCallableFixtureService);
    }

    public function test_it_does_throw_invalid_argument_exception_when_class_is_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot resolve');

        new DecoratorProxy(new DecorateWithMissingClassFixtureService);
    }
}

final class DecorateWithMissingClassFixtureService
{
    #[DecorateWith('NonExistentClass')]
    public function broken(): void {}
}

final class DecorateWithState
{
    /** @var array<int, string> */
    public static array $events = [];

    public static function reset(): void
    {
        self::$events = [];
    }
}

final class DecorateWithRecorder
{
    public function record(string $event): void
    {
        DecorateWithState::$events[] = $event;
    }
}

final class DecorateWithCallableFactory
{
    public static function staticWrap(callable $next): callable
    {
        return function (array $args) use ($next): mixed {
            DecorateWithState::$events[] = 'static:before';
            $result = $next($args);
            DecorateWithState::$events[] = 'static:after';

            return $result;
        };
    }

    public function instanceWrap(callable $next): callable
    {
        return function (array $args) use ($next): mixed {
            DecorateWithState::$events[] = 'instance:before';
            $result = $next($args);
            DecorateWithState::$events[] = 'instance:after';

            return $result;
        };
    }

    public static function returnsInvalid(callable $next): string
    {
        return 'not-callable';
    }
}

final class DecorateWithInvokableMiddleware
{
    public function __construct(private readonly DecorateWithRecorder $recorder) {}

    public function __invoke(callable $next): callable
    {
        return function (array $args) use ($next): mixed {
            $this->recorder->record('recorder:before');
            $result = $next($args);
            $this->recorder->record('recorder:after');

            return $result;
        };
    }
}

final class DecorateWithValidFixtureService
{
    #[DecorateWith('MykeMeynell\\Laravel\\Decorators\\Tests\\Unit\\decorate_with_global_wrapper')]
    public function viaGlobal(string $value): string
    {
        DecorateWithState::$events[] = 'core:global';

        return 'global:'.$value;
    }

    #[DecorateWith('MykeMeynell\\Laravel\\Decorators\\Tests\\Unit\\DecorateWithCallableFactory::staticWrap')]
    public function viaStatic(string $value): string
    {
        DecorateWithState::$events[] = 'core:static';

        return 'static:'.$value;
    }

    #[DecorateWith('MykeMeynell\\Laravel\\Decorators\\Tests\\Unit\\DecorateWithCallableFactory::instanceWrap')]
    public function viaInstanceString(string $value): string
    {
        DecorateWithState::$events[] = 'core:instance-string';

        return 'instance-string:'.$value;
    }

    #[DecorateWith(DecorateWithInvokableMiddleware::class)]
    public function viaInvokable(string $value): string
    {
        DecorateWithState::$events[] = 'core:invokable';

        return 'invokable:'.$value;
    }
}

final class DecorateWithBadReturnFixtureService
{
    #[DecorateWith('MykeMeynell\\Laravel\\Decorators\\Tests\\Unit\\DecorateWithCallableFactory::returnsInvalid')]
    public function broken(): string
    {
        return 'broken';
    }
}

final class DecorateWithMissingCallableFixtureService
{
    #[DecorateWith('MykeMeynell\\Laravel\\Decorators\\Tests\\Unit\\MissingCallable::wrap')]
    public function broken(): string
    {
        return 'broken';
    }
}
