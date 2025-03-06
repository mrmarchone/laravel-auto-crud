<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Mrmarchone\LaravelAutoCrud\Services\ModelService;

beforeEach(function () {
    $this->modelsPath = 'app/Models';
});

test('isModelExists returns model namespace if it exists', function () {
    $mockFile = Mockery::mock();
    $mockFile->shouldReceive('getRealPath')->andReturn('app/Models/User.php');
    $mockFile->shouldReceive('getFilename')->andReturn('User.php');

    File::shouldReceive('allFiles')->once()->andReturn([$mockFile]);

    File::shouldReceive('get')->with('app/Models/User.php')->andReturn('<?php namespace App\\Models; class User {}');

    expect(ModelService::isModelExists('User', $this->modelsPath))->toBe('App\\Models\\User');
});

test('isModelExists returns null if model does not exist', function () {
    File::shouldReceive('allFiles')->once()->andReturn([]);

    expect(ModelService::isModelExists('NonExistent', $this->modelsPath))->toBeNull();
});

test('resolveModelName correctly extracts model details', function () {
    $result = ModelService::resolveModelName('App\\Models\\User');

    expect($result)->toMatchArray([
        'modelName' => 'User',
        'folders' => null,
        'namespace' => 'App\\Models',
    ]);
});

test('handleModelsPath ensures trailing slash', function () {
    expect(ModelService::handleModelsPath('app/Models'))->toBe('app/Models/');
    expect(ModelService::handleModelsPath('app/Models/'))->toBe('app/Models/');
});

it('returns table name when model has namespace and is valid', function () {
    // Create an anonymous class extending Model
    $mockModel = new class extends Model
    {
        public function getTable()
        {
            return 'mock_table';
        }
    };

    // Mock the class creation
    $modelData = [
        'namespace' => 'App\\Models',
        'modelName' => 'TestModel',
    ];

    // Mock the class existence
    class_alias(get_class($mockModel), 'App\\Models\\TestModel');

    $result = ModelService::getFullModelNamespace($modelData);

    expect($result)->toBe('mock_table');
});

it('returns table name when model has no namespace and is valid', function () {
    $mockModel = new class extends Model
    {
        public function getTable()
        {
            return 'mock_table';
        }
    };

    $modelData = [
        'namespace' => '',
        'modelName' => 'TestModelNoNamespace',
    ];

    class_alias(get_class($mockModel), 'TestModelNoNamespace');

    $result = ModelService::getFullModelNamespace($modelData);

    expect($result)->toBe('mock_table');
});
