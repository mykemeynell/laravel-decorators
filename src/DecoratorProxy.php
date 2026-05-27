<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators;

use LogicException;
use MykeMeynell\Laravel\Decorators\Contracts\MethodDecorator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * Proxies method calls to a concrete service and applies attribute decorators.
 *
 * The proxy scans all public instance methods and pre-builds callable chains
 * from declared attributes implementing the decorator contract. At runtime it
 * dispatches calls through the chain or directly to the target when no
 * decorators are active for the method.
 *
 * @author Myke Meynell
 * @license MIT
 *
 * @since 1.0.0
 */
final class DecoratorProxy
{
    private readonly ReflectionClass $reflection;

    /** @var array<string, callable(array<int, mixed>): mixed> */
    private array $chains = [];

    /**
     * Create a new proxy around a target object.
     *
     * @param  object  $target  Target service to wrap.
     * @param  bool  $decoratorsEnabled  Whether to build and execute decorator chains.
     * @return void
     */
    public function __construct(
        private readonly object $target,
        private readonly bool $decoratorsEnabled = true,
    ) {
        $this->reflection = new ReflectionClass($target);

        if ($this->decoratorsEnabled) {
            try {
                $this->buildChains();
            } catch (LogicException $e) {
                throw $e;
            } catch (Throwable $e) {
                throw new LogicException('Failed to build decorator chains: '.$e->getMessage(), 0, $e);
            }
        }
    }

    /**
     * Build decorator chains for all public instance methods that declare decorators.
     */
    private function buildChains(): void
    {
        foreach ($this->reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || $method->isStatic() || $method->isAbstract()) {
                continue;
            }

            $decorators = $this->decoratorsFor($method);
            if ($decorators === []) {
                continue;
            }

            $context = $this->buildContext($method);
            $target = $this->target;
            $base = static fn (array $args): mixed => $method->invokeArgs($target, $args);

            $chain = array_reduce(
                array_reverse($decorators),
                function ($carry, MethodDecorator $decorator) use ($context) {
                    return $this->invokeWrap($decorator, $carry, $context);
                },
                $base,
            );

            $this->chains[$method->getName()] = $chain;
        }
    }

    /**
     * Resolve decorator attributes for a reflected method.
     *
     * @param  ReflectionMethod  $method  Reflected public method.
     * @return array<int, MethodDecorator> Decorator instances in declaration order.
     */
    private function decoratorsFor(ReflectionMethod $method): array
    {
        /** @var array<int, MethodDecorator> $decorators */
        $decorators = array_values(array_filter(
            array_map(
                static fn (ReflectionAttribute $attr): object => $attr->newInstance(),
                $method->getAttributes(),
            ),
            static fn (object $inst): bool => $inst instanceof MethodDecorator,
        ));

        return $decorators;
    }

    /**
     * Build execution context for decorators that consume method metadata.
     *
     * @param  ReflectionMethod  $method  Reflected method.
     * @return array<string, mixed> Method context map.
     */
    private function buildContext(ReflectionMethod $method): array
    {
        $className = $method->getDeclaringClass()->getName();
        $methodName = $method->getName();

        return [
            'class' => $className,
            'method' => $methodName,
            'callable' => $className.'::'.$methodName,
            'parameter_names' => array_map(
                static fn (\ReflectionParameter $parameter): string => $parameter->getName(),
                $method->getParameters(),
            ),
        ];
    }

    /**
     * Invoke a decorator while passing the optional method context argument.
     *
     * @param  MethodDecorator  $decorator  Decorator instance.
     * @param  callable(array<int, mixed>): mixed  $next  Next callable in the chain.
     * @param  array<string, mixed>  $context  Method context map.
     * @return callable(array<int, mixed>): mixed Wrapped callable.
     */
    private function invokeWrap(MethodDecorator $decorator, callable $next, array $context): callable
    {
        try {
            $wrapped = $decorator->wrap($next, $context);
        } catch (\TypeError $e) {
            throw new LogicException(sprintf(
                'Decorator "%s" must return a callable from wrap().',
                $decorator::class,
            ), 0, $e);
        }

        /** @phpstan-ignore-next-line */
        if (! is_callable($wrapped)) {
            throw new LogicException(sprintf(
                'Decorator "%s" must return a callable from wrap().',
                $decorator::class,
            ));
        }

        return $wrapped;
    }

    /**
     * Intercept a method call and dispatch through the decorator chain when present.
     *
     * @param  string  $name  Method name.
     * @param  array<int, mixed>  $arguments  Positional method arguments.
     * @return mixed Return value from the decorated method invocation.
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (isset($this->chains[$name])) {
            return ($this->chains[$name])($arguments);
        }

        return $this->target->$name(...$arguments);
    }

    /**
     * Proxy reading of undefined properties to the target object.
     *
     * @param  string  $name  Property name.
     * @return mixed Property value from the target.
     */
    public function __get(string $name): mixed
    {
        return $this->target->$name;
    }

    /**
     * Proxy writing of undefined properties to the target object.
     *
     * @param  string  $name  Property name.
     * @param  mixed  $value  Property value.
     */
    public function __set(string $name, mixed $value): void
    {
        $this->target->$name = $value;
    }

    /**
     * Determine whether a proxied property is set on the target object.
     *
     * @param  string  $name  Property name.
     * @return bool True when the property is set.
     */
    public function __isset(string $name): bool
    {
        return isset($this->target->$name);
    }

    /**
     * Return the original wrapped object.
     *
     * @return object Original concrete service instance.
     */
    public function unwrap(): object
    {
        return $this->target;
    }

    /**
     * List method names that currently have active decorator chains.
     *
     * @return array<int, string> Decorated public method names.
     */
    public function decoratedMethods(): array
    {
        return array_keys($this->chains);
    }
}
