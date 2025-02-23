<?php

use Mrmarchone\LaravelAutoCrud\Transformers\SpatieDataTransformer;

it('can convert to string', function () {
    $indent = str_repeat(' ', 4);
    $service = SpatieDataTransformer::convertDataToString(['name' => '#[Max(254)]']);
    expect($service)->toBe("$indent#[Max(254)]\n{$indent}name\n");
});

it('can convert to array', function () {
    $service = SpatieDataTransformer::convertNamespacesToString(['userNamespace', 'AnotherNamespace']);
    expect($service)->toBe("userNamespace\nAnotherNamespace\n");
});
