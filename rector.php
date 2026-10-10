<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withImportNames(
        removeUnusedImports: true,
    )
    ->withCache(
        cacheDirectory: __DIR__.'/.build/rector',
    )
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        InlineConstructorDefaultToPropertyRector::class,
        MakeInheritedMethodVisibilitySameAsParentRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        codingStyle: true,
    )
    ->withPhpSets();
