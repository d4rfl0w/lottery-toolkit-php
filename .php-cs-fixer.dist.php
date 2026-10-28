<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'single_import_per_statement' => true,
    ])
    ->setFinder(PhpCsFixer\Finder::create()->in([__DIR__.'/src', __DIR__.'/tests', __DIR__.'/examples', __DIR__.'/tools'])->exclude('var')->notName('reference.php'));
