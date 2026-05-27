<?php

declare(strict_types=1);

namespace MykeMeynell\Laravel\Decorators\Tests\Unit;

use MykeMeynell\Laravel\Decorators\Contracts\MethodDecorator;
use MykeMeynell\Laravel\Decorators\Tests\TestCase;
use ReflectionMethod;

final class MethodDecoratorTest extends TestCase
{
    public function test_it_does_define_wrap_contract_when_reflected(): void
    {
        $method = new ReflectionMethod(MethodDecorator::class, 'wrap');

        $this->assertSame('wrap', $method->getName());
        $this->assertCount(2, $method->getParameters());
        $this->assertTrue($method->hasReturnType());
        $this->assertSame('callable', (string) $method->getReturnType());
    }
}
