<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

class RepositoryBuilder extends BaseBuilder
{
    public function create(array $modelData, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'repository', 'Repositories', 'Repository', $overwrite, function ($modelData) {
            $model = $this->getFullModelNamespace($modelData);

            return [
                '{{ modelNamespace }}' => $model,
                '{{ model }}' => $modelData['modelName'],
                '{{ modelVariable }}' => lcfirst($modelData['modelName']),
            ];
        });
    }
}
