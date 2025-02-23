<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Illuminate\Support\Str;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;

class ControllerBuilder extends BaseBuilder
{
    public function createAPI(array $modelData, string $resource, string $request, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'api.controller', 'Http/Controllers/API', 'Controller', $overwrite, function ($modelData) use ($resource, $request) {
            $model = $this->getFullModelNamespace($modelData);
            $resourceName = explode('\\', $resource);
            $requestName = explode('\\', $request);

            return [
                '{{ requestNamespace }}' => $request,
                '{{ resourceNamespace }}' => $resource,
                '{{ modelNamespace }}' => $model,
                '{{ resource }}' => end($resourceName),
                '{{ request }}' => end($requestName),
                '{{ model }}' => $modelData['modelName'],
                '{{ modelVariable }}' => lcfirst($modelData['modelName']),
            ];
        });
    }

    public function createAPISpatieData(array $modelData, string $spatieData, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'api_spatie_data.controller', 'Http/Controllers/API', 'Controller', $overwrite, function ($modelData) use ($spatieData) {
            $model = $this->getFullModelNamespace($modelData);
            $spatieDataName = explode('\\', $spatieData);

            return [
                '{{ spatieDataNamespace }}' => $spatieData,
                '{{ modelNamespace }}' => $model,
                '{{ spatieData }}' => end($spatieDataName),
                '{{ model }}' => $modelData['modelName'],
                '{{ modelVariable }}' => lcfirst($modelData['modelName']),
            ];
        });
    }

    public function createAPIRepository(array $modelData, string $resource, string $request, string $service, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'api_repository.controller', 'Http/Controllers/API', 'Controller', $overwrite, function ($modelData) use ($resource, $request, $service) {
            $resourceName = explode('\\', $resource);
            $requestName = explode('\\', $request);
            $serviceName = explode('\\', $service);

            return [
                '{{ requestNamespace }}' => $request,
                '{{ resourceNamespace }}' => $resource,
                '{{ resource }}' => end($resourceName),
                '{{ request }}' => end($requestName),
                '{{ serviceNamespace }}' => $service,
                '{{ service }}' => end($serviceName),
                '{{ serviceVariable }}' => lcfirst(end($serviceName)),
            ];
        });
    }

    public function createAPIRepositorySpatieData(array $modelData, string $spatieData, string $service, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'api_repository_spatie_data.controller', 'Http/Controllers/API', 'Controller', $overwrite, function ($modelData) use ($spatieData, $service) {
            $spatieDataName = explode('\\', $spatieData);
            $serviceName = explode('\\', $service);

            return [
                '{{ spatieDataNamespace }}' => $spatieData,
                '{{ spatieData }}' => end($spatieDataName),
                '{{ serviceNamespace }}' => $service,
                '{{ service }}' => end($serviceName),
                '{{ serviceVariable }}' => lcfirst(end($serviceName)),
            ];
        });
    }

    public function createWeb(array $modelData, string $request, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'web.controller', 'Http/Controllers', 'Controller', $overwrite, function ($modelData) use ($request) {
            $model = $this->getFullModelNamespace($modelData);
            $requestName = explode('\\', $request);

            return [
                '{{ requestNamespace }}' => $request,
                '{{ modelNamespace }}' => $model,
                '{{ request }}' => end($requestName),
                '{{ model }}' => $modelData['modelName'],
                '{{ modelVariable }}' => lcfirst($modelData['modelName']),
                '{{ viewPath }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
                '{{ modelPlural }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
                '{{ routeName }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
            ];
        });
    }

    public function createWebRepository(array $modelData, string $request, string $service, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'web_repository.controller', 'Http/Controllers', 'Controller', $overwrite, function ($modelData) use ($service, $request) {
            $model = $this->getFullModelNamespace($modelData);
            $serviceName = explode('\\', $service);
            $requestName = explode('\\', $request);

            return [
                '{{ requestNamespace }}' => $request,
                '{{ request }}' => end($requestName),
                '{{ serviceNamespace }}' => $service,
                '{{ service }}' => end($serviceName),
                '{{ serviceVariable }}' => lcfirst(end($serviceName)),
                '{{ modelNamespace }}' => $model,
                '{{ model }}' => $modelData['modelName'],
                '{{ modelVariable }}' => lcfirst($modelData['modelName']),
                '{{ viewPath }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
                '{{ modelPlural }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
                '{{ routeName }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
            ];
        });
    }

    public function createWebSpatieData(array $modelData, string $spatieData, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'web_spatie_data.controller', 'Http/Controllers', 'Controller', $overwrite, function ($modelData) use ($spatieData) {
            $model = $this->getFullModelNamespace($modelData);
            $spatieDataName = explode('\\', $spatieData);

            return [
                '{{ spatieDataNamespace }}' => $spatieData,
                '{{ modelNamespace }}' => $model,
                '{{ spatieData }}' => end($spatieDataName),
                '{{ model }}' => $modelData['modelName'],
                '{{ modelVariable }}' => lcfirst($modelData['modelName']),
                '{{ viewPath }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
                '{{ modelPlural }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
                '{{ routeName }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
            ];
        });
    }

    public function createWebRepositorySpatieData(array $modelData, string $spatieData, string $service, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'web_repository_spatie_data.controller', 'Http/Controllers', 'Controller', $overwrite, function ($modelData) use ($service, $spatieData) {
            $model = $this->getFullModelNamespace($modelData);
            $serviceName = explode('\\', $service);
            $spatieDataName = explode('\\', $spatieData);

            return [
                '{{ spatieDataNamespace }}' => $spatieData,
                '{{ spatieData }}' => end($spatieDataName),
                '{{ serviceNamespace }}' => $service,
                '{{ service }}' => end($serviceName),
                '{{ serviceVariable }}' => lcfirst(end($serviceName)),
                '{{ modelNamespace }}' => $model,
                '{{ model }}' => $modelData['modelName'],
                '{{ modelVariable }}' => lcfirst($modelData['modelName']),
                '{{ viewPath }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
                '{{ modelPlural }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
                '{{ routeName }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
            ];
        });
    }
}
