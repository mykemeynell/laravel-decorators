<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Tests\Unit;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Mockery;
use MykeMeynell\Laravel\Decorators\DecoratorProxy;
use MykeMeynell\Laravel\Decorators\Decorators\Retry;
use MykeMeynell\Laravel\Decorators\Tests\TestCase;
use RuntimeException;

final class RetryDecoratorTest extends TestCase
{
    public function test_it_does_retry_and_preserve_return_value_when_transient_failure_occurs(): void
    {
        $service = new RetryFixtureService;
        $proxy = new DecoratorProxy($service);

        $result = $proxy->flaky();

        $this->assertSame('ok', $result);
        $this->assertSame(2, $service->flakyAttempts);
    }

    public function test_it_does_emit_warning_log_when_retrying_after_failure(): void
    {
        Log::spy();
        $proxy = new DecoratorProxy(new RetryFixtureService);

        $proxy->flaky();

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Decorator [Retry]: attempt failed, retrying', Mockery::on(static function (array $context): bool {
                return $context['attempt'] === 1
                    && $context['remaining'] === 2
                    && $context['exception'] === 'try-again';
            }));
        $this->assertTrue(true);
    }

    public function test_it_does_throw_final_exception_when_retry_attempts_are_exhausted(): void
    {
        $proxy = new DecoratorProxy(new RetryFixtureService);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('always-fails');

        $proxy->alwaysFails();
    }

    public function test_it_does_not_retry_when_exception_does_not_match_filter(): void
    {
        $service = new RetryFixtureService;
        $proxy = new DecoratorProxy($service);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no-match');

        try {
            $proxy->filtered();
        } finally {
            $this->assertSame(1, $service->filteredAttempts);
        }
    }
}

final class RetryFixtureService
{
    public int $flakyAttempts = 0;

    public int $filteredAttempts = 0;

    #[Retry(times: 3, delay: 0, backoff: 2.0, log: true)]
    public function flaky(): string
    {
        $this->flakyAttempts++;

        if ($this->flakyAttempts < 2) {
            throw new RuntimeException('try-again');
        }

        return 'ok';
    }

    #[Retry(times: 3, delay: 0, catch: [InvalidArgumentException::class], log: false)]
    public function filtered(): string
    {
        $this->filteredAttempts++;

        throw new RuntimeException('no-match');
    }

    #[Retry(times: 2, delay: 0, log: false)]
    public function alwaysFails(): string
    {
        throw new RuntimeException('always-fails');
    }
}
