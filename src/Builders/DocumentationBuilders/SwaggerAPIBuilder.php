<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders\DocumentationBuilders;

use Illuminate\Support\Str;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;
use Mrmarchone\LaravelAutoCrud\Services\ModelService;
use Mrmarchone\LaravelAutoCrud\Services\TableColumnsService;
use Mrmarchone\LaravelAutoCrud\Traits\TableColumnsTrait;

use function Laravel\Prompts\info;

class SwaggerAPIBuilder
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

        $oldSchemas = $oldPaths = [];

        if (! $overwrite) {
            if (file_exists($laravelAutoCrudPath.'/swagger-api.json')) {
                $fileContents = file_get_contents($laravelAutoCrudPath.'/swagger-api.json');
                $fileContents = json_decode($fileContents, true);
                $oldPaths = $fileContents['paths'] ?? [];
                $oldSchemas = $fileContents['components']['schemas'] ?? [];
            }
        }

        $oldPathsKeys = array_keys($oldPaths);
        $oldSchemasKeys = array_keys($oldSchemas);

        $model = HelperService::toSnakeCase(Str::plural($modelData['modelName']));

        $routeBase = config('app.url').'/api';

        $items = $schemas = [];

        $data = $this->getColumnsData($modelData);

        $endpoints = [
            ['POST', '', $data, 'Create '.$model],
            ['PATCH', '/{id}', $data, 'Update '.$model],
            ['DELETE', '/{id}', [], 'Delete '.$model],
            ['GET', '', [], 'Get '.$model],
            ['GET', '/{id}', [], 'Get single '.$model],
        ];

        $ucFirstModel = ucfirst($model);

        foreach ($endpoints as $endpoint) {
            [$method, $parameter] = $endpoint;
            $path = '/'.$model.$parameter;
            if (in_array($path, $oldPathsKeys)) {
                continue;
            }
            $description = $endpoint[3] ?? '';
            $requestBody = $endpoint[2] ?? [];
            $items[$path][strtolower($method)] = [
                'summary' => $description,
                'operationId' => HelperService::toSnakeCase($description),
                'tags' => [$ucFirstModel],
                'parameters' => ! empty($parameter) ? [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => [
                            'type' => 'integer',
                        ],
                    ]] : [],
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => [
                                        '$ref' => '#/components/schemas/'.$ucFirstModel,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            if (count($requestBody)) {
                $items[$path][strtolower($method)]['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/'.$ucFirstModel,
                            ],
                        ],
                    ],
                ];
            }
        }
        if (! in_array($ucFirstModel, $oldSchemasKeys)) {
            $schemas[$ucFirstModel] = [
                'type' => 'object',
            ];

            if (count($data)) {
                $schemas[$ucFirstModel]['properties'] = collect($data)->map(fn ($item) => collect($item)->filter(fn ($value, $key) => $key !== 'is_required'))->toArray();
                $schemas[$ucFirstModel]['required'] = collect($data)->where('is_required', true)->keys()->toArray();
            }
        }

        if (count($items)) {
            $oldPaths = array_merge($oldPaths, $items);
        }

        if (count($schemas)) {
            $oldSchemas = array_merge($oldSchemas, $schemas);
        }

        $newData = json_encode($this->buildSwaggerObject($routeBase, $oldPaths, $oldSchemas), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents($laravelAutoCrudPath.'/swagger-api.json', $newData);

        info("Updated: $laravelAutoCrudPath/swagger-api.json");
    }

    private function getColumnsData(array $modelData): array
    {
        $columns = $this->getAvailableColumns($modelData);
        $data = [];
        $is_enum = false;
        foreach ($columns as $column) {
            $columnName = $column['name'];
            switch ($column['type']) {
                case 'varchar':
                case 'char':
                case 'text':
                case 'tinytext':
                case 'mediumtext':
                case 'longtext':
                    $columnType = 'string';
                    $value = 'Example Value';
                    break;

                case 'int':
                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                case 'bigint':
                    $columnType = 'integer';
                    $value = 1;
                    break;

                case 'decimal':
                case 'numeric':
                case 'float':
                case 'double':
                    $columnType = 'number';
                    $value = 1.1;
                    break;

                case 'boolean':
                case 'bit':
                case 'tinyint(1)':
                    $columnType = 'boolean';
                    $value = true;
                    break;

                case 'date':
                case 'datetime':
                case 'timestamp':
                    $columnType = 'string';
                    $format = 'date-time';
                    $value = '2025-01-01 00:00:00';
                    break;

                case 'time':
                    $columnType = 'string';
                    $format = 'time';
                    $value = '00:00:00';
                    break;

                case 'json':
                case 'enum':
                case 'set':
                    $columnType = 'string';
                    $value = count($column['allowed_values']) ? $column['allowed_values'] : ['Value 1', 'Value 2'];
                    $is_enum = true;
                    $format = null;
                    break;

                case 'blob':
                case 'binary':
                case 'varbinary':
                case 'tinyblob':
                case 'mediumblob':
                case 'longblob':
                    $columnType = 'string';
                    $format = 'binary';
                    $value = 'Value';
                    break;

                default:
                    $columnType = 'string';
                    $value = 'Value';
            }

            $enumData = $is_enum ? ['enum' => $value] : ['example' => $value];

            $data[$columnName] = ['type' => $columnType, ...$enumData, 'is_required' => ! $column['is_nullable']];
            if (isset($format)) {
                $data[$columnName]['format'] = $format;
            }
        }

        return $data;
    }

    private function buildSwaggerObject(string $url, array $data, array $schemas = []): array
    {
        $appName = config('app.name');

        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => "Laravel Auto Crud ($appName)",
                'version' => '1.0.0',
                'description' => 'This is an automatic generated Swagger JSON file with full CRUD operations',
            ],
            'servers' => [
                [
                    'url' => $url,
                    'description' => 'Server',
                ],
            ],
            'paths' => $data,
            'components' => [
                'schemas' => $schemas,
            ],
        ];
    }
}
