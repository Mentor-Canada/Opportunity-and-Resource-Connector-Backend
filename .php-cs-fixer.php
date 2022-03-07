<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()->in([
    __DIR__ . '/web/modules/app',
    __DIR__ . '/tests'
])
    ->name('*.php')
    ->name('*.module')
;

$rules = [
    '@PSR12' => true,
    'array_indentation' => true,
    'ordered_imports' => true,
    'no_unused_imports' => true,
    'array_syntax' => ['syntax' => 'short'],
];

$config = new Config();

return $config->setFinder($finder)->setRules($rules);
