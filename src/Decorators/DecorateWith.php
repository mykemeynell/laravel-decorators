<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Decorators;

use Attribute;
use InvalidArgumentException;
use MykeMeynell\Laravel\Decorators\Contracts\MethodDecorator;
use ReflectionException;
use ReflectionMethod;
use Throwable;

/**
 * Delegates decoration behavior to an arbitrary callable.
 *
 * This attribute allows ad-hoc decoration without creating a dedicated
 * attribute class. Targets can be global functions, static methods, instance
 * methods resolved via the container, or invokable classes.
 *
 * @author Myke Meynell
 * @license MIT
 *
 * @since 1.0.0
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class DecorateWith implements MethodDecorator
{
    /**
     * Create a new delegated decorator definition.
     *
     * @param  string  $classOrFunction  Function name, class name, or Class::method string.
     * @param  string|null  $method  Optional instance method name when class is passed separately.
     * @return void
     */
    public function __construct(
        private readonly string $classOrFunction,
        private readonly ?string $method = null,
    ) {}

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException When the configured callable cannot be resolved.
     * @throws InvalidArgumentException When the resolved callable does not return a callable.
     */
    public function wrap(callable $next, array $context = []): callable
    {
        $callable = $this->resolve();
        $result = $callable($next);

        if (! is_callable($result)) {
            throw new InvalidArgumentException(sprintf(
                '[DecorateWith] The callable "%s" must return a callable, got "%s".',
                $this->describe(),
                get_debug_type($result),
            ));
        }

        return $result;
    }

    /**
     * Resolve constructor configuration into an executable callable.
     *
     * @return callable Callable that receives and wraps the next callable.
     *
     * @throws InvalidArgumentException When callable resolution fails.
     */
    private function resolve(): callable
    {
        if (str_contains($this->classOrFunction, '::')) {
            [$class, $method] = explode('::', $this->classOrFunction, 2);

            try {
                $refMethod = new ReflectionMethod($class, $method);
            } catch (ReflectionException $exception) {
                throw new InvalidArgumentException(sprintf(
                    '[DecorateWith] Cannot resolve "%s".',
                    $this->classOrFunction,
                ), 0, $exception);
            }

            if ($refMethod->isStatic()) {
                return [$class, $method];
            }

            try {
                return [app($class), $method];
            } catch (Throwable $exception) {
                throw new InvalidArgumentException(sprintf(
                    '[DecorateWith] Cannot resolve "%s" through the container.',
                    $class,
                ), 0, $exception);
            }
        }

        if ($this->method !== null) {
            try {
                $instance = app($this->classOrFunction);
            } catch (Throwable $exception) {
                throw new InvalidArgumentException(sprintf(
                    '[DecorateWith] Cannot resolve "%s" through the container.',
                    $this->classOrFunction,
                ), 0, $exception);
            }

            if (! is_callable([$instance, $this->method])) {
                throw new InvalidArgumentException(sprintf(
                    '[DecorateWith] Method "%s::%s" is not callable.',
                    $this->classOrFunction,
                    $this->method,
                ));
            }

            return [$instance, $this->method];
        }

        if (class_exists($this->classOrFunction)) {
            try {
                $instance = app($this->classOrFunction);
            } catch (Throwable $exception) {
                throw new InvalidArgumentException(sprintf(
                    '[DecorateWith] Cannot resolve "%s" through the container.',
                    $this->classOrFunction,
                ), 0, $exception);
            }

            if (! is_callable($instance)) {
                throw new InvalidArgumentException(sprintf(
                    '[DecorateWith] Class "%s" must be invokable.',
                    $this->classOrFunction,
                ));
            }

            return $instance;
        }

        if (function_exists($this->classOrFunction)) {
            return $this->classOrFunction;
        }

        throw new InvalidArgumentException(sprintf(
            '[DecorateWith] Cannot resolve "%s" as a callable. '
            .'Expected a global function name, a class name, or a "Class::method" string.',
            $this->classOrFunction,
        ));
    }

    /**
     * Build a human-readable callable description for diagnostics.
     *
     * @return string Callable description string.
     */
    private function describe(): string
    {
        if ($this->method !== null) {
            return $this->classOrFunction.'::'.$this->method;
        }

        return $this->classOrFunction;
    }
}
