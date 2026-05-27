<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Tests\Unit;

use MykeMeynell\Laravel\Decorators\DecoratorProxy;
use MykeMeynell\Laravel\Decorators\Facades\Decorator;
use MykeMeynell\Laravel\Decorators\Tests\TestCase;

final class DecoratorFacadeTest extends TestCase
{
    public function test_it_does_make_proxy_when_facade_make_is_called(): void
    {
        $proxy = Decorator::make(FacadeFixtureService::class, ['value' => 'from-make']);

        $this->assertInstanceOf(DecoratorProxy::class, $proxy);
        $this->assertSame('from-make', $proxy->value());
    }

    public function test_it_does_wrap_proxy_when_facade_wrap_is_called(): void
    {
        $proxy = Decorator::wrap(new FacadeFixtureService('from-wrap'));

        $this->assertInstanceOf(DecoratorProxy::class, $proxy);
        $this->assertSame('from-wrap', $proxy->value());
    }

    public function test_it_does_bind_singleton_proxy_when_facade_singleton_is_called(): void
    {
        $proxy = Decorator::singleton(FacadeFixtureService::class, ['value' => 'from-singleton']);

        $resolved = $this->app->make(FacadeFixtureService::class);

        $this->assertSame($proxy, $resolved);
        $this->assertInstanceOf(DecoratorProxy::class, $resolved);
        $this->assertSame('from-singleton', $resolved->value());
    }
}

final class FacadeFixtureService
{
    public function __construct(private readonly string $value = 'default') {}

    public function value(): string
    {
        return $this->value;
    }
}
