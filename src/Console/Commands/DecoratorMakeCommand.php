<?php

namespace MykeMeynell\Laravel\Decorators\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class DecoratorMakeCommand extends GeneratorCommand
{
    /**
     * {@inheritDoc}
     */
    protected $signature = 'make:decorator {name}';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Create a new decorator class';

    /**
     * {@inheritDoc}
     */
    protected $type = 'Decorator';

    /**
     * {@inheritDoc}
     */
    protected function getStub()
    {
        return realpath(__DIR__.'/../../../stubs/decorator.stub');
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Decorators';
    }

    /**
     * {@inheritDoc}
     */
    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        return str_replace(
            search: '{{ class }}',
            replace: $class,
            subject: $stub
        );
    }
}
