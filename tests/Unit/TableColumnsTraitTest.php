<?php

use Mrmarchone\LaravelAutoCrud\Services\ModelService;
use Mrmarchone\LaravelAutoCrud\Services\TableColumnsService;
use Mrmarchone\LaravelAutoCrud\Traits\TableColumnsTrait;


it('returns available columns from TableColumnsService', function () {

    $testClass = new class {
        use TableColumnsTrait;

        public function __construct()
        {
            $this->modelService = new ModelService;
            $this->tableColumnsService = new TableColumnsService;
        }
    };

    // Arrange: إنشاء Mocks للخدمات
    $mockTableColumnsService = Mockery::mock(TableColumnsService::class);
    $mockModelService = Mockery::mock(ModelService::class);

    // بيانات تجريبية
    $modelData = ['modelName' => 'User'];
    $fullNamespace = 'App\\Models\\User';
    $expectedColumns = ['id', 'name', 'email'];

    // ضبط الـ Mock للسلوك المتوقع
    $mockModelService->shouldReceive('getFullModelNamespace')
        ->once()
        ->with($modelData)
        ->andReturn($fullNamespace);

    $mockTableColumnsService->shouldReceive('getAvailableColumns')
        ->once()
        ->with($fullNamespace)
        ->andReturn($expectedColumns);

    // Act: إنشاء كائن من الكلاس التجريبي اللي بيستخدم الـ Trait
    $result = $testClass->getAvailableColumns($modelData);

    // Assert: التأكد من صحة النتيجة
    expect($result)->toBe($expectedColumns);
});


afterEach(function () {
    Mockery::close();
});
