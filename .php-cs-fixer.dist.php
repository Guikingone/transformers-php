<?php

declare(strict_types=1);

use PhpCsFixer\Runner\Parallel\ParallelConfig;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->ignoreVCSIgnored(true)
    ->exclude(['.github', 'docs', 'includes', 'scripts', 'libs', 'vendor'])
;

return (new PhpCsFixer\Config())
    ->setUnsupportedPhpVersionAllowed(true)
    ->setParallelConfig(new ParallelConfig())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR1' => true,
        '@PSR2' => true,
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        '@PHP81Migration' => true,
        'concat_space' => ['spacing' => 'one'],
        'array_syntax' => ['syntax' => 'short'],
        'phpdoc_align' => ['align' => 'left'],
        'class_definition' => [
            'multi_line_extends_each_single_line' => true,
        ],
        'linebreak_after_opening_tag' => true,
        'declare_strict_types' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'native_constant_invocation' => true,
        'native_function_casing' => true,
        'native_function_invocation' => [
            'include' => ['@internal'],
            'scope' => 'namespaced',
        ],
        'no_php4_constructor' => true,
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'remove_inheritdoc' => true,
        ],
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_imports' => [
            'imports_order' => [
                'class',
                'function',
                'const',
            ],
        ],
        'array_indentation' => true,
        'multiline_whitespace_before_semicolons' => true,
        'static_lambda' => true,
        'single_line_throw' => false,
        'visibility_required' => [
            'elements' => [
                'property',
                'method',
                'const',
            ],
        ],
        'trailing_comma_in_multiline' => [
            'elements' => [
                'arrays',
                'arguments',
                'parameters',
            ],
        ],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
    ])
    ->setFinder($finder)
;
