<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Tests\Unit;

use Illuminate\Support\Facades\Log;
use Mockery;
use MykeMeynell\Laravel\Decorators\DecoratorProxy;
use MykeMeynell\Laravel\Decorators\Decorators\Log as LogDecorator;
use MykeMeynell\Laravel\Decorators\Tests\TestCase;
use RuntimeException;

final class LogDecoratorTest extends TestCase
{
    public function test_it_does_log_success_and_preserve_return_value_when_method_completes(): void
    {
        Log::spy();
        $proxy = new DecoratorProxy(new LogFixtureService);

        $result = $proxy->success('alice');

        $this->assertSame('ok:alice', $result);
        Log::shouldHaveReceived('info')
            ->once()
            ->with('Decorator: method call', Mockery::on(static function (array $context): bool {
                return $context['args'] === ['alice']
                    && $context['result'] === 'ok:alice'
                    && isset($context['time_ms']);
            }));
    }

    public function test_it_does_hide_arguments_when_log_args_is_disabled(): void
    {
        Log::spy();
        $proxy = new DecoratorProxy(new LogFixtureService);

        $proxy->hiddenArgs('secret');

        Log::shouldHaveReceived('debug')
            ->once()
            ->with('Decorator: method call', Mockery::on(static function (array $context): bool {
                return $context['args'] === '[hidden]' && $context['result'] === 'hidden';
            }));
        $this->assertTrue(true);
    }

    public function test_it_does_log_error_and_rethrow_when_method_throws(): void
    {
        Log::spy();
        $proxy = new DecoratorProxy(new LogFixtureService);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('log-failure');

        try {
            $proxy->failing();
        } finally {
            Log::shouldHaveReceived('error')
                ->once()
                ->with('Decorator: method threw', Mockery::on(static function (array $context): bool {
                    return $context['exception'] === 'log-failure'
                        && isset($context['time_ms']);
                }));
        }
    }
}

final class LogFixtureService
{
    #[LogDecorator(level: 'info')]
    public function success(string $name): string
    {
        return 'ok:'.$name;
    }

    #[LogDecorator(level: 'debug', logArgs: false)]
    public function hiddenArgs(string $token): string
    {
        return 'hidden';
    }

    #[LogDecorator(level: 'warning')]
    public function failing(): string
    {
        throw new RuntimeException('log-failure');
    }
}
