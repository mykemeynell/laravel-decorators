<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Decorators;

use Attribute;
use Illuminate\Support\Facades\Log as LaravelLog;
use MykeMeynell\Laravel\Decorators\Contracts\MethodDecorator;
use Throwable;

/**
 * Logs method invocation outcomes for decorated methods.
 *
 * The decorator records successful and failed executions, including argument
 * payloads when enabled and wall-clock duration measurements. It supports
 * configurable channels and log levels using Laravel's logging facade.
 *
 * @author Myke Meynell
 * @license MIT
 *
 * @since 1.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Log implements MethodDecorator
{
    /**
     * Create a new logging decorator.
     *
     * @param  string  $level  PSR-compatible log level method name.
     * @param  bool  $logArgs  Whether to include raw arguments in log context.
     * @param  string|null  $channel  Optional Laravel log channel name.
     * @return void
     */
    public function __construct(
        private readonly string $level = 'debug',
        private readonly bool $logArgs = true,
        private readonly ?string $channel = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function wrap(callable $next, array $context = []): callable
    {
        return function (array $args) use ($next): mixed {
            $channel = $this->channel ?? $this->config('decorators.log_channel');
            $logger = $channel
                ? LaravelLog::channel($channel)
                : LaravelLog::getFacadeRoot();

            $start = hrtime(true);

            try {
                $result = $next($args);
                $ms = round((hrtime(true) - $start) / 1e6, 2);

                $logger->{$this->level}('Decorator: method call', [
                    'args' => $this->logArgs ? $args : '[hidden]',
                    'result' => $result,
                    'time_ms' => $ms,
                ]);

                return $result;
            } catch (Throwable $e) {
                $ms = round((hrtime(true) - $start) / 1e6, 2);

                $logger->error('Decorator: method threw', [
                    'args' => $this->logArgs ? $args : '[hidden]',
                    'exception' => $e->getMessage(),
                    'time_ms' => $ms,
                ]);

                throw $e;
            }
        };
    }

    /**
     * Read a value from Laravel config when available.
     *
     * @param  string  $key  Config key.
     * @param  mixed  $default  Default value.
     * @return mixed Config value or default.
     */
    private function config(string $key, mixed $default = null): mixed
    {
        if (! function_exists('app')) {
            return $default;
        }

        $app = app();
        if (! $app->bound('config')) {
            return $default;
        }

        $config = $app['config'];

        return $config->get($key, $default);
    }
}
