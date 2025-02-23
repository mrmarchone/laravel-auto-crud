<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Illuminate\Support\Facades\Schema;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;
use Mrmarchone\LaravelAutoCrud\Services\ModelService;

class ResourceBuilder extends BaseBuilder
{
    public function create(array $modelData, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'resource', 'Http/Resources', 'Resource', $overwrite, function ($modelData) {
            return ['{{ data }}' => HelperService::formatArrayToPhpSyntax($this->getResourcesData($modelData), true)];
        });
    }

    private function getResourcesData(array $modelData): array
    {
        $table = ModelService::getFullModelNamespace($modelData);
        $columns = Schema::getColumnListing($table);
        $data = [];
        foreach ($columns as $column) {
            $data["$column"] = '$this->'.$column;
        }

        return $data;
    }
}
