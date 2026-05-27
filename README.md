# Laravel Decorators

> Python/TypeScript-style method decorators for Laravel services, powered by PHP 8 attributes.

This package provides a clean, attribute-based way to apply cross-cutting concerns (logging, caching, retries, etc.) to your Laravel service methods. It uses a lightweight proxy pattern to intercept method calls and wrap them in a decorator chain.

## Requirements

- **PHP:** `>=8.2`
- **Laravel:** `^11.0`, `^12.0`, or `^13.0`

## Installation

```bash
composer require mykemeynell/laravel-decorators
```

## Quick Start

### 1. Add Attributes to Your Service

```php
namespace App\Services;

use MykeMeynell\Laravel\Decorators\Decorators\Log;
use MykeMeynell\Laravel\Decorators\Decorators\Cache;

class UserService
{
    #[Log]
    #[Cache(ttl: 3600)]
    public function findUser(int $id): array
    {
        return User::findOrFail($id)->toArray();
    }
}
```

### 2. Resolve the Decorated Service

You can resolve your service through the `Decorator` facade to ensure it is wrapped in the proxy:

```php
use MykeMeynell\Laravel\Decorators\Facades\Decorator;
use App\Services\UserService;

$service = Decorator::make(UserService::class);
$user = $service->findUser(1); // Call is logged and cached!
```

## Built-in Decorators

### `#[Log]`
Records method calls, arguments, and execution time to Laravel logs.

```php
#[Log(level: 'info', logArgs: true, channel: 'stack')]
```
- `level`: PSR-compatible log level (default: `debug`).
- `logArgs`: Whether to include raw arguments in the log (default: `true`).
- `channel`: The Laravel log channel to use (default: config `log_channel`).

### `#[Cache]`
Caches the method return value based on its identity and arguments.

```php
#[Cache(ttl: 3600, store: 'redis', tags: ['users'], prefix: 'u:')]
```
- `ttl`: Time-to-live in seconds. Use `0` to cache forever (default: `3600`).
- `store`: The cache store to use (default: config `cache_store`).
- `tags`: Array of cache tags for taggable stores (default: `[]`).
- `prefix`: Key prefix (default: config `cache_prefix`).

### `#[Retry]`
Transparently retries failed method calls with configurable backoff.

```php
#[Retry(times: 3, delay: 100, backoff: 2.0, catch: [ServiceException::class])]
```
- `times`: Maximum attempts including the first call (default: `3`).
- `delay`: Base delay in milliseconds between retries (default: `0`).
- `backoff`: Multiplier for exponential backoff (default: `1.0`).
- `catch`: Array of exception classes to retry on (default: `[]`, catches all `Throwable`).
- `log`: Whether to log retry attempts (default: `true`).

### `#[RateLimit]`
Throttles method execution using Laravel's rate limiter.

```php
#[RateLimit(maxAttempts: 5, decaySeconds: 60, key: 'my-bucket')]
```
- `maxAttempts`: Max calls within the window (default: `60`).
- `decaySeconds`: Window duration in seconds (default: `60`).
- `key`: Optional fixed bucket identifier. By default, keys are unique to method + arguments.

### `#[Transactional]`
Wraps the method execution in a database transaction.

```php
#[Transactional(connection: 'mysql', attempts: 2)]
```
- `connection`: Database connection name (default: default connection).
- `attempts`: Number of times to retry the transaction on deadlock (default: `1`).

### `#[Validate]`
Validates method arguments using Laravel's validator before execution.

```php
#[Validate(['id' => 'required|integer', 'email' => 'required|email'])]
```
- `rules`: Array of validation rules. Can be keyed by parameter name or index.

### `#[Deprecated]`
Emits a deprecation warning when the method is called.

```php
#[Deprecated('Use newMethod() instead.')]
```
- `message`: Custom deprecation message. Emits `E_USER_DEPRECATED` in local/testing environments.

### `#[DecorateWith]`
Delegates decoration behavior to an arbitrary callable. This is useful for ad-hoc decoration without creating a dedicated attribute class.

```php
#[DecorateWith(MyCustomWrapper::class)]
#[DecorateWith('App\Decorators\MyDecorator::handle')]
#[DecorateWith('my_global_decorator_function')]
public function myMethod() { ... }
```

- `classOrFunction`: A class name (must be invokable), a `Class::method` string, or a global function name.
- `method`: Optional method name if providing a class name separately.

**The callable must return another callable** that performs the actual wrapping:

```php
class MyCustomWrapper
{
    public function __invoke(callable $next): callable
    {
        return function (array $args) use ($next) {
            // Pre-processing
            $result = $next($args);
            // Post-processing
            return $result;
        };
    }
}
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="decorators-config"
```

### Auto-Decoration

You can configure certain classes to be automatically decorated when resolved from the Laravel container:

```php
// config/decorators.php
return [
    'decorate' => [
        App\Contracts\PaymentProcessor::class,
    ],
];
```

## Creating Custom Decorators

Implement the `MethodDecorator` interface:

```php
namespace App\Decorators;

use Attribute;
use MykeMeynell\Laravel\Decorators\Contracts\MethodDecorator;

#[Attribute(Attribute::TARGET_METHOD)]
class MyCustomDecorator implements MethodDecorator
{
    public function wrap(callable $next, array $context = []): callable
    {
        return function (array $args) use ($next) {
            // Logic before
            $result = $next($args);
            // Logic after
            return $result;
        };
    }
}
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
