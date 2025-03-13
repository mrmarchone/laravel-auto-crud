<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders\DocumentationBuilders;

use Illuminate\Support\Str;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;
use Mrmarchone\LaravelAutoCrud\Services\ModelService;
use Mrmarchone\LaravelAutoCrud\Services\TableColumnsService;
use Mrmarchone\LaravelAutoCrud\Traits\TableColumnsTrait;

use function Laravel\Prompts\info;

class PostmanBuilder
{
    use TableColumnsTrait;

    public function __construct()
    {
        $this->modelService = new ModelService;
        $this->tableColumnsService = new TableColumnsService;
    }

    public function create(array $modelData, bool $overwrite = false): void
    {
        $laravelAutoCrudPath = base_path('laravel-auto-crud');
        if (! file_exists($laravelAutoCrudPath)) {
            mkdir($laravelAutoCrudPath, 0755, true);
        }

        $oldItems = [];

        if (! $overwrite) {
            if (file_exists($laravelAutoCrudPath.'/postman.json')) {
                $fileContents = file_get_contents($laravelAutoCrudPath.'/postman.json');
                $fileContents = json_decode($fileContents, true);
                $oldItems = $fileContents['item'] ?? [];
            }
        }

        $oldModels = array_values(array_column($oldItems, 'name'));

        if (count($oldModels)) {
            $oldModels = array_combine(range(1, count($oldModels)), $oldModels);
        }

        $model = HelperService::toSnakeCase(Str::plural($modelData['modelName']));

        $routeBase = sprintf(
            config('app.url').'/api/%s',
            $model
        );

        $parsedUrl = parse_url($routeBase);

        $items = [
            'name' => ucfirst($model),
        ];

        if (! in_array($items['name'], $oldModels)) {
            $data = $this->getColumnsData($modelData);

            $endpoints = [
                ['POST', '', $data, 'Create '.$model],
                ['PATCH', '/:id', $data, 'Update '.$model],
                ['DELETE', '/:id', [], 'Delete '.$model],
                ['GET', '', [], 'Get '.$model],
                ['GET', '/:id', [], 'Get single '.$model],
            ];

            foreach ($endpoints as $endpoint) {
                [$method, $path] = $endpoint;
                $data = $endpoint[2] ?? [];
                $items['item'][] = [
                    'name' => $endpoint[3],
                    'request' => [
                        'method' => $method,
                        'header' => [
                            [
                                'key' => 'Accept',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'Content-Type',
                                'value' => 'application/json',
                                'type' => 'text',
                            ],
                        ],
                        'body' => [
                            'mode' => 'raw',
                            'raw' => json_encode($data, JSON_PRETTY_PRINT),
                            'options' => [
                                'raw' => [
                                    'language' => 'json',
                                ],
                            ],
                        ],
                        'url' => [
                            'raw' => $routeBase.$path,
                            'protocol' => $parsedUrl['scheme'],
                            'host' => explode('.', $parsedUrl['host']),
                            'port' => $parsedUrl['port'] ?? 80,
                            'path' => array_merge(explode('/', substr($parsedUrl['path'], 1)), ! empty($path) ? [substr($path, 1)] : []),
                            'variable' => ! empty($path) ? [
                                [
                                    'key' => 'id',
                                    'value' => '1',
                                ],
                            ] : [],
                        ],
                    ],
                    'response' => [
                    ],
                ];
            }

            $oldItems[] = $items;

            $newData = json_encode($this->buildPostmanObject(count($oldItems) ? $oldItems : [$items]), JSON_PRETTY_PRINT);
            file_put_contents($laravelAutoCrudPath.'/postman.json', $newData);
        }

        info("Updated: $laravelAutoCrudPath/postman.json");
    }

    private function getColumnsData(array $modelData): array
    {
        $columns = $this->getAvailableColumns($modelData);
        $data = [];

        foreach ($columns as $column) {
            $columnName = $column['name'];
            $data[$columnName] = 'value';
        }

        return $data;
    }

    private function buildPostmanObject(array $data): array
    {
        $appName = config('app.name');

        return [
            'info' => [
                'name' => "Laravel Auto Crud ($appName)",
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [
                ...$data,
            ],
        ];
    }
}
