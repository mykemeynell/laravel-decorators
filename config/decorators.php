<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global Enable / Disable
    |--------------------------------------------------------------------------
    | Set to false to bypass all decorator chains entirely (useful in tests).
    */
    'enabled' => env('DECORATORS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Log Decorator Defaults
    |--------------------------------------------------------------------------
    | Channel used by #[Log]. null falls back to the default Laravel channel.
    */
    'log_channel' => env('DECORATORS_LOG_CHANNEL', null),

    /*
    |--------------------------------------------------------------------------
    | Cache Decorator Defaults
    |--------------------------------------------------------------------------
    | Store used by #[Cache]. null falls back to the default cache store.
    | Prefix is prepended to every generated cache key.
    */
    'cache_store' => env('DECORATORS_CACHE_STORE', null),
    'cache_prefix' => env('DECORATORS_CACHE_PREFIX', 'decorator:'),

    /*
    |--------------------------------------------------------------------------
    | Auto-Decorate Bindings
    |--------------------------------------------------------------------------
    | List concrete class names here and the service provider will call
    | $app->extend() on each one, wrapping the resolved instance in a
    | DecoratorProxy automatically — no manual wiring needed.
    |
    | Example:
    |   'decorate' => [
    |       App\Services\UserService::class,
    |       App\Services\PaymentService::class,
    |   ],
    */
    'decorate' => [],

];
