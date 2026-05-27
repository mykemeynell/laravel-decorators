<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Contracts;

/**
 * Defines the contract for method decorators used by the proxy pipeline.
 *
 * Implementations receive the next callable in the invocation chain and return
 * a new callable that wraps it. This mirrors Laravel middleware composition and
 * enables attribute-driven cross-cutting behavior on service methods.
 *
 * @author Myke Meynell
 * @license MIT
 *
 * @since 1.0.0
 */
interface MethodDecorator
{
    /**
     * Wrap the next callable in the decorator chain.
     *
     * @param  callable(array<int, mixed>): mixed  $next  Next callable in the chain.
     * @param  array<string, mixed>  $context  Optional method metadata context.
     * @return callable(array<int, mixed>): mixed Callable that wraps the next callable.
     */
    public function wrap(callable $next, array $context = []): callable;
}
