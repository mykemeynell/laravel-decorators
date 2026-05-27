<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Decorators;

use Attribute;
use Illuminate\Support\Facades\Log;
use MykeMeynell\Laravel\Decorators\Contracts\MethodDecorator;

/**
 * Emits a deprecation warning whenever the decorated method is invoked.
 *
 * The decorator writes a warning through Laravel logging and optionally emits
 * a native deprecation notice in local or testing environments so developers
 * can detect usage during tests and interactive debugging.
 *
 * @author Myke Meynell
 * @license MIT
 *
 * @since 1.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Deprecated implements MethodDecorator
{
    /**
     * Create a new deprecation decorator.
     *
     * @param  string  $message  Deprecation warning message shown in logs and notices.
     * @return void
     */
    public function __construct(
        private readonly string $message = 'This method is deprecated.',
    ) {}

    /**
     * {@inheritdoc}
     */
    public function wrap(callable $next, array $context = []): callable
    {
        return function (array $args) use ($next): mixed {
            Log::warning("[Deprecated] {$this->message}");

            if (app()->environment('local', 'testing')) {
                trigger_error($this->message, E_USER_DEPRECATED);
            }

            return $next($args);
        };
    }
}
