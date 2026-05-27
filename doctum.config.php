<?php

use Doctum\Doctum;
use Symfony\Component\Finder\Finder;

$dir = realpath(__DIR__.'/src');

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in($dir);

return new Doctum($iterator, [
    'title' => 'Laravel Decorators Documentation',
    'base_url' => getenv('DOCS_URL') ?: 'http://localhost:9091',
    'build_dir' => __DIR__.'/docs',
]);
