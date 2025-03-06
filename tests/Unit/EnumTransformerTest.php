<?php

use Mrmarchone\LaravelAutoCrud\Transformers\EnumTransformer;

it('can transform enumerations', function () {
    $indent = str_repeat(' ', 4);
    $service = EnumTransformer::convertDataToString(['name']);
    expect($service)->toBe("{$indent}case name = 'name';"."\n");
});
