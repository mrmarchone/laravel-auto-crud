<?php

use Illuminate\Support\Facades\File;
use Mrmarchone\LaravelAutoCrud\Services\ModelService;

beforeEach(function () {
    $this->app->setBasePath(__DIR__ . '/../');
    File::partialMock();
});

describe('handling models path', function () {
    it('handle models path without slash', function () {
        $service = ModelService::handleModelsPath('app/Models/User.php');
        expect($service)->toBe('app/Models/User.php/');
    });

    it('handle models path with slash', function () {
        $service = ModelService::handleModelsPath('app/Models/User.php/');
        expect($service)->toBe('app/Models/User.php/');
    });

    it('resolves model name', function () {
        $service = ModelService::resolveModelName('User');
        expect($service)->toBe([
            'modelName' => 'User',
            'folders' => '',
            'namespace' => null,
        ]);
    });

    it('resolves model name if exists inside app/models', function () {
        $service = ModelService::resolveModelName('App\\Models\\User');
        expect($service)->toBe([
            'modelName' => 'User',
            'folders' => null,
            'namespace' => 'App\\Models',
        ]);
    });


    it('resolves model name if exists inside sub-dir inside app/Models', function () {
        $service = ModelService::resolveModelName('App\\Models\\TestingFolder\\User');
        expect($service)->toBe([
            'modelName' => 'User',
            'folders' => 'App/Models/TestingFolder',
            'namespace' => 'App\\Models\\TestingFolder',
        ]);
    });
});

describe('handling namespace', function () {
    it('returns the full namespace when a model exists with the given name', function () {
        // Act
        $result = ModelService::isModelExists('User', 'Models');

        // Assert
        expect($result)->toBe('Tests\\Models\\User');
    });


    it('returns null when a model not exists with the given name', function () {
        // Act
        $result = ModelService::isModelExists('Project', 'Models');
        // Assert
        expect($result)->toBeNull();
    });
});
