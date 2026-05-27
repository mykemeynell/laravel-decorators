<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Tests\Unit;

use Illuminate\Support\Facades\File;
use MykeMeynell\Laravel\Decorators\Tests\TestCase;

final class DecoratorMakeCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        if (File::exists(app_path('Decorators'))) {
            File::deleteDirectory(app_path('Decorators'));
        }

        parent::tearDown();
    }

    public function test_it_can_generate_a_decorator(): void
    {
        $this->artisan('make:decorator', ['name' => 'TestDecorator'])
            ->assertExitCode(0);

        $this->assertTrue(File::exists(app_path('Decorators/TestDecorator.php')));

        $content = File::get(app_path('Decorators/TestDecorator.php'));

        $this->assertStringContainsString('namespace App\Decorators;', $content);
        $this->assertStringContainsString('final class TestDecorator implements MethodDecorator', $content);
        $this->assertStringContainsString('public function wrap(callable $next, array $context = []): callable', $content);
        $this->assertStringContainsString('// TODO: Implement decorator...', $content);
    }

    public function test_it_can_generate_a_decorator_in_nested_namespace(): void
    {
        $this->artisan('make:decorator', ['name' => 'Custom/SubDecorator'])
            ->assertExitCode(0);

        $this->assertTrue(File::exists(app_path('Decorators/Custom/SubDecorator.php')));

        $content = File::get(app_path('Decorators/Custom/SubDecorator.php'));

        $this->assertStringContainsString('namespace App\Decorators\Custom;', $content);
        $this->assertStringContainsString('final class SubDecorator implements MethodDecorator', $content);
        $this->assertStringContainsString('// TODO: Implement decorator...', $content);
    }

    public function test_it_can_generate_a_decorator_with_full_namespace(): void
    {
        $this->artisan('make:decorator', ['name' => 'App\Decorators\FullyQualifiedDecorator'])
            ->assertExitCode(0);

        $this->assertTrue(File::exists(app_path('Decorators/FullyQualifiedDecorator.php')));

        $content = File::get(app_path('Decorators/FullyQualifiedDecorator.php'));
        $this->assertStringContainsString('namespace App\Decorators;', $content);
        $this->assertStringContainsString('final class FullyQualifiedDecorator', $content);
        $this->assertStringContainsString('// TODO: Implement decorator...', $content);
    }

    public function test_it_handles_already_existing_decorator(): void
    {
        $this->artisan('make:decorator', ['name' => 'ExistingDecorator'])
            ->assertExitCode(0);

        $this->artisan('make:decorator', ['name' => 'ExistingDecorator'])
            ->expectsOutputToContain('Decorator already exists')
            ->assertExitCode(0);
    }
}
