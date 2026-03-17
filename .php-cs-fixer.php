<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/public',
        __DIR__ . '/tests',
        __DIR__ . '/utils',
        __DIR__ . '/templates',
        __DIR__ . '/scripts',
    ])
    ->name('*.php')
    ->append([new SplFileInfo(__DIR__ . '/preload.php')]);

return (new Config())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS' => true,
        'strict_param' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
        'new_expression_parentheses' => true,
        'use_arrow_functions' => true,
        'function_declaration' => ['closure_fn_spacing' => 'none'],
        'braces_position' => true,
        'single_line_comment_style' => false,
        'multiline_comment_opening_closing' => false,
        'no_empty_comment' => false,
        'no_trailing_whitespace_in_comment' => false,
    ]);