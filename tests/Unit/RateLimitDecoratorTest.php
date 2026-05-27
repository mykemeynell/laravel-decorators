<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Tests\Unit;

use Illuminate\Support\Facades\RateLimiter;
use MykeMeynell\Laravel\Decorators\DecoratorProxy;
use MykeMeynell\Laravel\Decorators\Decorators\RateLimit;
use MykeMeynell\Laravel\Decorators\Tests\TestCase;
use RuntimeException;

final class RateLimitDecoratorTest extends TestCase
{
    public function test_it_does_allow_call_and_preserve_return_value_when_under_limit(): void
    {
        RateLimiter::clear('decorator:rate:fixed-key');
        $proxy = new DecoratorProxy(new RateLimitFixtureService);

        $result = $proxy->limited('abc');

        $this->assertSame('limited:abc', $result);
    }

    public function test_it_does_increment_rate_limit_counter_when_method_is_called(): void
    {
        RateLimiter::clear('decorator:rate:fixed-key');
        RateLimiter::spy();
        $proxy = new DecoratorProxy(new RateLimitFixtureService);

        $proxy->limited('abc');

        RateLimiter::shouldHaveReceived('hit')->once()->with('decorator:rate:fixed-key', 60);
        $this->assertTrue(true);
    }

    public function test_it_does_throw_runtime_exception_when_limit_is_exceeded(): void
    {
        RateLimiter::clear('decorator:rate:fixed-key');
        $proxy = new DecoratorProxy(new RateLimitFixtureService);

        $proxy->limited('abc');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Rate limit exceeded.');

        $proxy->limited('abc');
    }

    public function test_it_does_generate_method_scoped_key_when_fixed_key_is_not_provided(): void
    {
        $generatedKey = 'decorator:rate:'.sha1(serialize([RateLimitFixtureService::class.'::dynamic', ['abc']]));
        RateLimiter::clear($generatedKey);

        $proxy = new DecoratorProxy(new RateLimitFixtureService);
        $this->assertSame('dynamic:abc', $proxy->dynamic('abc'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Rate limit exceeded.');

        $proxy->dynamic('abc');
    }
}

final class RateLimitFixtureService
{
    #[RateLimit(maxAttempts: 1, decaySeconds: 60, key: 'fixed-key')]
    public function limited(string $value): string
    {
        return 'limited:'.$value;
    }

    #[RateLimit(maxAttempts: 1, decaySeconds: 60)]
    public function dynamic(string $value): string
    {
        return 'dynamic:'.$value;
    }
}
