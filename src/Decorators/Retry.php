<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Decorators;

use Attribute;
use Illuminate\Support\Facades\Log;
use MykeMeynell\Laravel\Decorators\Contracts\MethodDecorator;
use Throwable;

/**
 * Retries decorated method calls after eligible exceptions.
 *
 * The decorator retries a callable up to a configured attempt count, supports
 * optional exception filtering, and can apply exponential backoff delays
 * between retries while emitting warning logs for each retry event.
 *
 * @author Myke Meynell
 * @license MIT
 *
 * @since 1.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Retry implements MethodDecorator
{
    /**
     * Create a new retry decorator.
     *
     * @param  int  $times  Maximum attempts including the first call.
     * @param  int  $delay  Base retry delay in milliseconds.
     * @param  float  $backoff  Multiplier for each subsequent retry delay.
     * @param  array<int, class-string<Throwable>>  $catch  Retry only when thrown exception matches one of these classes.
     * @param  bool  $log  Whether to emit a warning log for retried failures.
     * @return void
     */
    public function __construct(
        private readonly int $times = 3,
        private readonly int $delay = 0,
        private readonly float $backoff = 1.0,
        private readonly array $catch = [],
        private readonly bool $log = true,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function wrap(callable $next, array $context = []): callable
    {
        return function (array $args) use ($next): mixed {
            $attempt = 0;

            while (true) {
                try {
                    return $next($args);
                } catch (Throwable $e) {
                    $attempt++;

                    if (! empty($this->catch)) {
                        $matches = array_reduce(
                            $this->catch,
                            fn (bool $carry, string $class): bool => $carry || $e instanceof $class,
                            false,
                        );

                        if (! $matches) {
                            throw $e;
                        }
                    }

                    if ($attempt >= $this->times) {
                        throw $e;
                    }

                    if ($this->log) {
                        Log::warning('Decorator [Retry]: attempt failed, retrying', [
                            'attempt' => $attempt,
                            'remaining' => $this->times - $attempt,
                            'exception' => $e->getMessage(),
                        ]);
                    }

                    if ($this->delay > 0) {
                        $sleepMs = $this->delay * ($this->backoff ** max(0, $attempt - 1));
                        usleep((int) round($sleepMs * 1_000));
                    }
                }
            }
        };
    }
}
