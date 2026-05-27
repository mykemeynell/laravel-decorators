<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Facades;

use Illuminate\Support\Facades\Facade;
use MykeMeynell\Laravel\Decorators\DecoratorFactory;
use MykeMeynell\Laravel\Decorators\DecoratorProxy;

/**
 * Provides static access to the package decorator factory binding.
 *
 * This facade forwards calls to the singleton `DecoratorFactory` instance so
 * consumers can resolve and wrap services without manually pulling the factory
 * from the container.
 *
 * @method static DecoratorProxy make(string $abstract, array $parameters = [])
 * @method static DecoratorProxy wrap(object $target)
 * @method static DecoratorProxy singleton(string $abstract, array $parameters = [])
 *
 * @author Myke Meynell
 * @license MIT
 *
 * @since 1.0.0
 */
class Decorator extends Facade
{
    /**
     * Resolve the service container binding behind this facade.
     *
     * @return string Service container accessor for the underlying binding.
     */
    protected static function getFacadeAccessor(): string
    {
        return DecoratorFactory::class;
    }
}
