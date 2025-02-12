<?php
declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;


class RepositoryBuilder extends BaseBuilder
{
    public function create(array $modelData, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'repository', 'Repositories', 'Repository', $overwrite, function ($modelData) {
            $model = $modelData['namespace'] ? 'App\\Models\\' . $modelData['namespace'] . '\\' . $modelData['modelName'] : 'App\\Models\\' . $modelData['modelName'];
            return [
                '{{ modelNamespace }}' => $model,
                '{{ model }}' => $modelData['modelName'],
                '{{ modelVariable }}' => lcfirst($modelData['modelName'])
            ];
        });
    }
}
