<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use MykeMeynell\Laravel\Decorators\Console\Commands\DecoratorMakeCommand;

/**
 * Registers package services, configuration, and auto-decoration hooks.
 *
 * The provider binds the decorator factory as a singleton and optionally
 * configures container extensions so selected concrete services are
 * transparently wrapped in the proxy at resolution time.
 *
 * @author Myke Meynell
 * @license MIT
 *
 * @since 1.0.0
 */
class DecoratorServiceProvider extends ServiceProvider
{
    /**
     * Register package bindings and merge package configuration.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/decorators.php',
            'decorators',
        );

        $this->app->singleton(DecoratorFactory::class, function (Container $app): DecoratorFactory {
            $config = $app->bound('config')
                ? $app['config']
                : null;

            return new DecoratorFactory(
                $app,
                $config instanceof ConfigRepository ? $config : null,
            );
        });

        $this->app->alias(DecoratorFactory::class, 'decorator');
    }

    /**
     * Boot provider features that require the application to be fully loaded.
     */
    public function boot(): void
    {
        $this->publishConfig();
        $this->registerAutoDecorateBindings();
    }

    /**
     * Register publishable configuration file mappings for artisan.
     */
    private function publishConfig(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/decorators.php' => config_path('decorators.php'),
            ], 'decorators-config');

            $this->commands([DecoratorMakeCommand::class]);
        }
    }

    /**
     * Extend configured bindings so resolved services are proxied automatically.
     */
    private function registerAutoDecorateBindings(): void
    {
        /** @var ConfigRepository|null $config */
        $config = $this->app->bound('config') ? $this->app['config'] : null;
        if (! $config instanceof ConfigRepository) {
            return;
        }

        if (! (bool) $config->get('decorators.enabled', true)) {
            return;
        }

        /** @var class-string[] $classes */
        $classes = (array) $config->get('decorators.decorate', []);

        foreach ($classes as $abstract) {
            $this->app->extend($abstract, function (object $service, Container $app): DecoratorProxy {
                return $app->make(DecoratorFactory::class)->wrap($service);
            });
        }
    }
}
