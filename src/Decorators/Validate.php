<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Decorators;

use Attribute;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use MykeMeynell\Laravel\Decorators\Contracts\MethodDecorator;

/**
 * Validates decorated method arguments using Laravel's validator.
 *
 * Numeric rule keys are mapped to reflected method parameter names when
 * context is available from the proxy. String rule keys should match parameter
 * names to ensure data is extracted correctly for validation.
 *
 * @author Myke Meynell
 * @license MIT
 *
 * @since 1.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Validate implements MethodDecorator
{
    /**
     * Create a new validation decorator.
     *
     * @param  array<int|string, string>  $rules  Validation rules keyed by index or parameter name.
     * @return void
     */
    public function __construct(
        private readonly array $rules = [],
    ) {}

    /**
     * {@inheritdoc}
     */
    public function wrap(callable $next, array $context = []): callable
    {
        return function (array $args) use ($next, $context): mixed {
            $parameterNames = $this->parameterNames($context);
            $normalizedRules = $this->normalizeRules($this->rules, $parameterNames);
            $data = $this->extractData($normalizedRules, $args, $parameterNames);

            $validator = Validator::make($data, $normalizedRules);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            return $next($args);
        };
    }

    /**
     * Extract ordered parameter names from method context.
     *
     * @param  array<string, mixed>  $context  Decorator context map.
     * @return array<int, string> Parameter names ordered by argument position.
     */
    private function parameterNames(array $context): array
    {
        $names = $context['parameter_names'] ?? [];
        if (! is_array($names)) {
            return [];
        }

        return array_values(array_filter(
            $names,
            static fn (mixed $name): bool => is_string($name) && $name !== '',
        ));
    }

    /**
     * Convert numeric rule keys into string field names.
     *
     * @param  array<int|string, string>  $rules  Raw rules configured on the attribute.
     * @param  array<int, string>  $parameterNames  Reflected method parameter names.
     * @return array<string, string> Rules keyed by validator field names.
     */
    private function normalizeRules(array $rules, array $parameterNames): array
    {
        $normalized = [];

        foreach ($rules as $key => $rule) {
            if (is_int($key)) {
                $normalized[$parameterNames[$key] ?? (string) $key] = $rule;

                continue;
            }

            $normalized[(string) $key] = $rule;
        }

        return $normalized;
    }

    /**
     * Build the validator data array from positional method arguments.
     *
     * @param  array<string, string>  $rules  Normalized validation rules.
     * @param  array<int, mixed>  $args  Positional method arguments.
     * @param  array<int, string>  $parameterNames  Reflected method parameter names.
     * @return array<string, mixed> Validator data keyed by field name.
     */
    private function extractData(array $rules, array $args, array $parameterNames): array
    {
        $data = [];
        $nameToIndex = array_flip($parameterNames);

        foreach (array_keys($rules) as $field) {
            if (array_key_exists($field, $nameToIndex)) {
                $data[$field] = $args[$nameToIndex[$field]] ?? null;

                continue;
            }

            if (is_numeric($field)) {
                $index = (int) $field;
                $data[$field] = $args[$index] ?? null;

                continue;
            }

            $data[$field] = null;
        }

        return $data;
    }
}
