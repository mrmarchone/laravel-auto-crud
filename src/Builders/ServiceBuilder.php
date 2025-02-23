<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

class ServiceBuilder extends BaseBuilder
{
    public function create(array $modelData, string $repository, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'service', 'Services', 'Service', $overwrite, function ($modelData) use ($repository) {
            $model = $this->getFullModelNamespace($modelData);
            $repositorySplitting = explode('\\', $repository);
            $repositoryNamespace = $repository;
            $repository = end($repositorySplitting);
            $repositoryVariable = lcfirst($repository);

            return [
                '{{ modelNamespace }}' => $model,
                '{{ model }}' => $modelData['modelName'],
                '{{ modelVariable }}' => lcfirst($modelData['modelName']),
                '{{ repository }}' => $repository,
                '{{ repositoryNamespace }}' => $repositoryNamespace,
                '{{ repositoryVariable }}' => $repositoryVariable,
            ];
        });
    }
}
