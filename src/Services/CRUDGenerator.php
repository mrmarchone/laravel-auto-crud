<?php
declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Services;

use InvalidArgumentException;
use Mrmarchone\LaravelAutoCrud\Builders\ControllerBuilder;
use Mrmarchone\LaravelAutoCrud\Builders\CURLBuilder;
use Mrmarchone\LaravelAutoCrud\Builders\RepositoryBuilder;
use Mrmarchone\LaravelAutoCrud\Builders\RequestBuilder;
use Mrmarchone\LaravelAutoCrud\Builders\ResourceBuilder;
use Mrmarchone\LaravelAutoCrud\Builders\RouteBuilder;
use Mrmarchone\LaravelAutoCrud\Builders\ServiceBuilder;
use Mrmarchone\LaravelAutoCrud\Builders\SpatieDataBuilder;
use Mrmarchone\LaravelAutoCrud\Builders\ViewBuilder;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class CRUDGenerator
{
    private CURLBuilder $CURLBuilder;
    private ControllerBuilder $controllerBuilder;
    private ResourceBuilder $resourceBuilder;
    private RequestBuilder $requestBuilder;
    private RouteBuilder $routeBuilder;
    private ViewBuilder $viewBuilder;
    private RepositoryBuilder $repositoryBuilder;
    private ServiceBuilder $serviceBuilder;
    private SpatieDataBuilder $spatieDataBuilder;

    public function __construct()
    {
        $this->CURLBuilder = new CURLBuilder();
        $this->controllerBuilder = new ControllerBuilder();
        $this->resourceBuilder = new ResourceBuilder();
        $this->requestBuilder = new RequestBuilder();
        $this->routeBuilder = new RouteBuilder();
        $this->viewBuilder = new ViewBuilder();
        $this->repositoryBuilder = new RepositoryBuilder();
        $this->serviceBuilder = new ServiceBuilder();
        $this->spatieDataBuilder = new SpatieDataBuilder();
    }

    public function generate($modelData, array $options): void
    {
        $checkForType = $this->askControllerType($options['type']);

        if ($options['pattern'] == 'spatie-data') {
            $spatieDataName = $this->spatieDataBuilder->create($modelData, $options['overwrite']);
        } else {
            $requestName = $this->requestBuilder->create($modelData, $options['overwrite']);
        }

        $repository = $service = null;
        if ($options['repository']) {
            $repository = $this->repositoryBuilder->create($modelData, $options['overwrite']);
            $service = $this->serviceBuilder->create($modelData, $repository, $options['overwrite']);
        }
        $data = [
            'requestName' => $requestName ?? '',
            'repository' => $repository ?? '',
            'service' => $service ?? '',
            'spatieData' => $spatieDataName ?? '',
        ];
        $controllerName = $this->generateController($checkForType, $modelData, $data, $options);
        $this->routeBuilder->create($modelData['modelName'], $controllerName, $checkForType);

        info('Auto CRUD files generated successfully for ' . $modelData['modelName']);
    }

    private function askControllerType(string $type = null): string
    {
        return $type ?? text(
            label: 'Do you want to create an api or web controller?',
            default: 'api',
            required: true,
            validate: function ($value) {
                if (!in_array(strtolower($value), ['api', 'web'])) {
                    return 'Please enter a valid type api or web';
                }
                return null;
            },
            hint: 'Write api or web',
        );
    }

    private function generateController(string $type, array $modelData, array $data, array $options): string
    {
        if ($type === 'api') {
            return $this->generateAPIController($modelData, $data['requestName'], $data['repository'], $data['service'], $options, $data['spatieData']);
        }

        if ($type === 'web') {
            return $this->generateWebController($modelData, $data['requestName'], $data['repository'], $data['service'], $options, $data['spatieData']);
        }

        throw new InvalidArgumentException("Unsupported controller type: $type");
    }

    private function generateAPIController(array $modelData, string $requestName, string $repository, string $service, array $options, string $spatieData = null): string
    {
        if ($options['pattern'] == 'spatie-data') {
            $controllerName = $repository
                ? $this->controllerBuilder->createAPIRepositorySpatieData($modelData, $spatieData, $service, $options['overwrite'])
                : $this->controllerBuilder->createAPISpatieData($modelData, $spatieData, $options['overwrite']);
        } else {
            $resourceName = $this->resourceBuilder->create($modelData, $options['overwrite']);
            $controllerName = $repository
                ? $this->controllerBuilder->createAPIRepository($modelData, $resourceName, $requestName, $service, $options['overwrite'])
                : $this->controllerBuilder->createAPI($modelData, $resourceName, $requestName, $options['overwrite']);
        }

        $this->CURLBuilder->create($modelData);
        return $controllerName;
    }

    private function generateWebController(array $modelData, string $requestName, string $repository, string $service, array $options, string $spatieData = ''): string
    {
        if ($options['pattern'] == 'spatie-data') {
            $controllerName = $repository
                ? $this->controllerBuilder->createWebRepositorySpatieData($modelData, $spatieData, $service, $options['overwrite'])
                : $this->controllerBuilder->createWebSpatieData($modelData, $spatieData, $options['overwrite']);
        } else {
            $controllerName = $repository
                ? $this->controllerBuilder->createWebRepository($modelData, $requestName, $service, $options['overwrite'])
                : $this->controllerBuilder->createWeb($modelData, $requestName, $options['overwrite']);
        }

        $this->viewBuilder->create($modelData['modelName']);
        return $controllerName;
    }
}
