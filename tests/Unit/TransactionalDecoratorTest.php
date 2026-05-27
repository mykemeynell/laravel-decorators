<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Tests\Unit;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Mockery;
use MykeMeynell\Laravel\Decorators\DecoratorProxy;
use MykeMeynell\Laravel\Decorators\Decorators\Transactional;
use MykeMeynell\Laravel\Decorators\Tests\TestCase;
use RuntimeException;

final class TransactionalDecoratorTest extends TestCase
{
    public function test_it_does_wrap_method_in_transaction_and_preserve_return_value_on_success(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        $connection->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(static fn (callable $callback, int $attempts): mixed => $callback());

        DB::swap($connection);
        $proxy = new DecoratorProxy(new TransactionalFixtureService);

        $result = $proxy->defaultConnection('abc');

        $this->assertSame('default:abc', $result);
    }

    public function test_it_does_use_named_connection_and_attempts_when_configured(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        $connection->shouldReceive('transaction')
            ->once()
            ->with(Mockery::type('callable'), 2)
            ->andReturnUsing(static fn (callable $callback, int $attempts): mixed => $callback());

        DB::shouldReceive('connection')
            ->once()
            ->with('custom')
            ->andReturn($connection);

        $proxy = new DecoratorProxy(new TransactionalFixtureService);

        $this->assertSame('custom:abc', $proxy->customConnection('abc'));
    }

    public function test_it_does_rethrow_exception_when_transaction_callback_throws(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        $connection->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(static function (callable $callback, int $attempts): mixed {
                return $callback();
            });

        DB::swap($connection);
        $proxy = new DecoratorProxy(new TransactionalFixtureService);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('txn-fail');

        $proxy->failing();
    }
}

final class TransactionalFixtureService
{
    #[Transactional]
    public function defaultConnection(string $value): string
    {
        return 'default:'.$value;
    }

    #[Transactional(connection: 'custom', attempts: 2)]
    public function customConnection(string $value): string
    {
        return 'custom:'.$value;
    }

    #[Transactional]
    public function failing(): string
    {
        throw new RuntimeException('txn-fail');
    }
}
