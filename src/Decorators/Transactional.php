<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Decorators;

use Attribute;
use Illuminate\Support\Facades\DB;
use MykeMeynell\Laravel\Decorators\Contracts\MethodDecorator;

/**
 * Wraps decorated method execution in a database transaction.
 *
 * The decorator can target a specific database connection and allows the
 * native transaction retry attempts argument for transient deadlock scenarios.
 * Any exception from the decorated callable is propagated by the database
 * transaction handler after rollback.
 *
 * @author Myke Meynell
 * @license MIT
 *
 * @since 1.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Transactional implements MethodDecorator
{
    /**
     * Create a new transactional decorator.
     *
     * @param  string|null  $connection  Connection name; null uses the default connection.
     * @param  int  $attempts  Number of attempts used by DB::transaction for retryable failures.
     * @return void
     */
    public function __construct(
        private readonly ?string $connection = null,
        private readonly int $attempts = 1,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function wrap(callable $next, array $context = []): callable
    {
        return function (array $args) use ($next): mixed {
            $db = $this->connection !== null
                ? DB::connection($this->connection)
                : DB::getFacadeRoot();

            return $db->transaction(
                fn (): mixed => $next($args),
                $this->attempts,
            );
        };
    }
}
