<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves services and wraps them in decorator-aware proxies.
 *
 * This factory centralizes object creation for decorated services, ensuring
 * constructor dependencies are resolved by the container before proxying.
 * It also supports rebinding a decorated proxy as a singleton for repeated
 * resolution through standard Laravel container APIs.
 *
 * @author Myke Meynell
 * @license MIT
 *
 * @since 1.0.0
 */
final class DecoratorFactory
{
    /**
     * Create a new factory instance.
     *
     * @param  Container  $container  Laravel container used for object resolution.
     * @param  ConfigRepository|null  $config  Optional config repository for non-helper config access.
     * @return void
     */
    public function __construct(
        private readonly Container $container,
        private readonly ?ConfigRepository $config = null,
    ) {}

    /**
     * Resolve a class through the container and return its decorated proxy.
     *
     * @template T of object
     *
     * @param  class-string<T>  $abstract  Class or binding identifier to resolve.
     * @param  array<string, mixed>  $parameters  Additional container make parameters.
     * @return DecoratorProxy Proxy instance that forwards calls to the resolved service.
     */
    public function make(string $abstract, array $parameters = []): DecoratorProxy
    {
        /** @var T $instance */
        $instance = $this->container->make($abstract, $parameters);

        return $this->wrap($instance);
    }

    /**
     * Wrap an existing object in a decorator proxy.
     *
     * @param  object  $target  Pre-built object instance.
     * @return DecoratorProxy Proxy around the provided object.
     */
    public function wrap(object $target): DecoratorProxy
    {
        return new DecoratorProxy($target, $this->decoratorsEnabled());
    }

    /**
     * Resolve an object, decorate it, and bind that proxy as a singleton.
     *
     * @param  class-string  $abstract  Class or binding identifier to resolve.
     * @param  array<string, mixed>  $parameters  Additional container make parameters.
     * @return DecoratorProxy Decorated singleton proxy now bound in the container.
     */
    public function singleton(string $abstract, array $parameters = []): DecoratorProxy
    {
        $proxy = $this->make($abstract, $parameters);

        $this->container->instance($abstract, $proxy);

        return $proxy;
    }

    /**
     * Determine whether decorator chains should be built and executed.
     *
     * @return bool True when decorators are enabled.
     */
    private function decoratorsEnabled(): bool
    {
        if ($this->config !== null) {
            return (bool) $this->config->get('decorators.enabled', true);
        }

        if ($this->container->bound('config')) {
            $repository = $this->container->make('config');

            return (bool) $repository->get('decorators.enabled', true);
        }

        return true;
    }
}
