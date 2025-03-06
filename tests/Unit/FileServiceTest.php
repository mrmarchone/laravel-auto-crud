<?php

use Illuminate\Support\Facades\File;
use Mrmarchone\LaravelAutoCrud\Services\FileService;

beforeEach(function () {
    $this->app->setBasePath(__DIR__.'/../');
    File::partialMock();
});

it('can create from stub file', function () {
    $modelData = [
        'modelName' => 'User',
        'folders' => null,
        'namespace' => null,
    ];
    $service = Mockery::mock(FileService::class)->makePartial();

    $service->shouldReceive('createFromStub')
        ->withArgs([$modelData, 'enum', 'Enums', 'Enum'])
        ->andReturn('App\\Enums\\UserEnum');

    $results = $service->createFromStub($modelData, 'enum', 'Enums', 'Enum'); // Call on mock

    expect($results)->toBe('App\\Enums\\UserEnum');

});
