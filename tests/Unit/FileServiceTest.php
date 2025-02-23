<?php


use Illuminate\Support\Facades\File;
use Mrmarchone\LaravelAutoCrud\Services\FileService;
use Mrmarchone\LaravelAutoCrud\Transformers\EnumTransformer;

//uses(\Tests\TestCase::class)->in(__DIR__);

beforeEach(function () {
    $this->app->setBasePath(__DIR__ . '/../');
    File::partialMock();
});

//it('can create from stub file', function () {
//    $modelData = [
//        'modelName' => 'User',
//        'folders' => null,
//        'namespace' => null,
//    ];
//    $service = new FileService();
//    $r = $service->createFromStub($modelData, 'enum', 'Enums', 'Enum');
//    dd($r);
//});
