<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mockery;
use MykeMeynell\Laravel\Decorators\DecoratorProxy;
use MykeMeynell\Laravel\Decorators\Decorators\Validate;
use MykeMeynell\Laravel\Decorators\Tests\TestCase;

final class ValidateDecoratorTest extends TestCase
{
    public function test_it_does_validate_arguments_and_preserve_return_value_when_rules_pass(): void
    {
        $proxy = new DecoratorProxy(new ValidateFixtureService);

        $result = $proxy->byIndex(3);

        $this->assertSame('count:3', $result);
    }

    public function test_it_does_build_string_keys_for_validator_when_rules_are_indexed(): void
    {
        $factory = Validator::getFacadeRoot();
        Validator::shouldReceive('make')
            ->once()
            ->with(
                Mockery::on(static fn (array $data): bool => array_key_exists('count', $data) && ! array_key_exists(0, $data)),
                Mockery::on(static fn (array $rules): bool => array_key_exists('count', $rules) && ! array_key_exists(0, $rules)),
            )
            ->andReturnUsing(static fn (array $data, array $rules) => $factory->make($data, $rules));

        $proxy = new DecoratorProxy(new ValidateFixtureService);

        $proxy->byIndex(3);
        $this->assertTrue(true);
    }

    public function test_it_does_throw_validation_exception_when_rules_fail(): void
    {
        $proxy = new DecoratorProxy(new ValidateFixtureService);

        $this->expectException(ValidationException::class);

        $proxy->byName('invalid-email');
    }

    public function test_it_does_validate_when_rules_use_numeric_string_keys(): void
    {
        $decorator = new Validate(['0' => 'required|integer|min:2']);
        $wrapped = $decorator->wrap(
            static fn (array $args): string => 'ok',
            ['parameter_names' => []],
        );

        $this->assertSame('ok', $wrapped([2]));
    }

    public function test_it_does_set_unknown_named_fields_to_null_when_extracting_data(): void
    {
        $decorator = new Validate(['missing' => 'required']);
        $wrapped = $decorator->wrap(
            static fn (array $args): string => 'ok',
            ['parameter_names' => ['count']],
        );

        $this->expectException(ValidationException::class);
        $wrapped([2]);
    }
}

final class ValidateFixtureService
{
    #[Validate([0 => 'required|integer|min:2'])]
    public function byIndex(int $count): string
    {
        return 'count:'.$count;
    }

    #[Validate(['email' => 'required|email'])]
    public function byName(string $email): string
    {
        return $email;
    }
}
