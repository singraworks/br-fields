<?php

declare(strict_types=1);

use SingraWorks\BrFields\Documents\Cpf;
use SingraWorks\BrFields\Exceptions\BrFieldsException;
use SingraWorks\BrFields\Exceptions\InvalidDocument;

mutates(InvalidDocument::class);

it('can be caught through the package marker or as an invalid argument', function (): void {
    try {
        Cpf::from('x');
        $this->fail('Expected InvalidDocument');
    } catch (BrFieldsException $brFieldsException) {
        expect($brFieldsException)->toBeInstanceOf(InvalidArgumentException::class);
    }
});
