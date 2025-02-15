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
    }

    public function generate($modelData, array $options): void
    {
        $checkForType = $this->askControllerType($options['type']);
        $requestName = $this->requestBuilder->create($modelData, $options['overwrite']);

        $repository = $service = null;
        if ($options['repository']) {
            $repository = $this->repositoryBuilder->create($modelData, $options['overwrite']);
            $service = $this->serviceBuilder->create($modelData, $repository, $options['overwrite']);
        }

        $controllerName = $this->generateController($checkForType, $modelData, $requestName, $repository, $service, $options);
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

    private function generateController($type, $modelData, $requestName, $repository, $service, $options)
    {
        if ($type === 'api') {
            return $this->generateAPIController($modelData, $requestName, $repository, $service, $options);
        }

        if ($type === 'web') {
            return $this->generateWebController($modelData, $requestName, $repository, $service, $options);
        }

        throw new InvalidArgumentException("Unsupported controller type: $type");
    }

    private function generateAPIController($modelData, $requestName, $repository, $service, $options)
    {
        $resourceName = $this->resourceBuilder->create($modelData, $options['overwrite']);

        $controllerName = $repository
            ? $this->controllerBuilder->createAPIRepository($modelData, $resourceName, $requestName, $service, $options['overwrite'])
            : $this->controllerBuilder->createAPI($modelData, $resourceName, $requestName, $options['overwrite']);

        $this->CURLBuilder->create($modelData);
        return $controllerName;
    }

    private function generateWebController($modelData, $requestName, $repository, $service, $options)
    {
        $controllerName = $repository
            ? $this->controllerBuilder->createWebRepository($modelData, $requestName, $service, $options['overwrite'])
            : $this->controllerBuilder->createWeb($modelData, $requestName, $options['overwrite']);

        $this->viewBuilder->create($modelData['modelName']);
        return $controllerName;
    }
}
