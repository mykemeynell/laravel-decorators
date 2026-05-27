<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Tests\Unit;

use Illuminate\Support\Facades\Log;
use MykeMeynell\Laravel\Decorators\DecoratorProxy;
use MykeMeynell\Laravel\Decorators\Decorators\Deprecated;
use MykeMeynell\Laravel\Decorators\Tests\TestCase;
use RuntimeException;

final class DeprecatedDecoratorTest extends TestCase
{
    public function test_it_does_log_warning_and_preserve_return_value_when_method_is_called(): void
    {
        Log::spy();
        $proxy = new DecoratorProxy(new DeprecatedFixtureService);

        $warnings = [];
        set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
            if ($severity === E_USER_DEPRECATED) {
                $warnings[] = $message;

                return true;
            }

            return false;
        });

        try {
            $result = $proxy->legacy('abc');
        } finally {
            restore_error_handler();
        }

        $this->assertSame('legacy:abc', $result);
        $this->assertSame(['Use modernMethod instead.'], $warnings);
        Log::shouldHaveReceived('warning')->once()->with('[Deprecated] Use modernMethod instead.');
    }

    public function test_it_does_propagate_exception_when_wrapped_method_fails(): void
    {
        Log::spy();
        $proxy = new DecoratorProxy(new DeprecatedFixtureService);

        set_error_handler(static fn (int $severity, string $message): bool => $severity === E_USER_DEPRECATED);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('deprecated-failure');

        try {
            $proxy->legacyFail();
        } finally {
            restore_error_handler();
            Log::shouldHaveReceived('warning')->once()->with('[Deprecated] This call is deprecated.');
        }
    }
}

final class DeprecatedFixtureService
{
    #[Deprecated('Use modernMethod instead.')]
    public function legacy(string $value): string
    {
        return 'legacy:'.$value;
    }

    #[Deprecated('This call is deprecated.')]
    public function legacyFail(): string
    {
        throw new RuntimeException('deprecated-failure');
    }
}
