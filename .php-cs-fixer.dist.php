<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
//    ->exclude('vendor')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
//        '@PSR12' => true,
//        'array_syntax' => ['syntax' => 'short'],
//        'ordered_imports' => ['sort_algorithm' => 'alpha'],
//        'no_unused_imports' => true,
//        'single_quote' => true,
//        'trailing_comma_in_multiline' => true,
    ])
    ->setFinder($finder)
//    ->setRiskyAllowed(true)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
;
