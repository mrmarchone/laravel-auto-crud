<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;
use Mrmarchone\LaravelAutoCrud\Services\ModelService;

class PostmanBuilder
{
    public function create(array $modelData)
    {
        $laravelAutoCrudPath = base_path('laravel-auto-crud');
        if (! file_exists($laravelAutoCrudPath)) {
            mkdir($laravelAutoCrudPath, 0755, true);
        }

        $oldItems = [];

        if (file_exists($laravelAutoCrudPath.'/postman.json')) {
            $fileContents = file_get_contents($laravelAutoCrudPath.'/postman.json');
            $fileContents = json_decode($fileContents, true);
            $oldItems = $fileContents['item'] ?? [];
        }

        $model = HelperService::toSnakeCase(Str::plural($modelData['modelName']));

        $routeBase = sprintf(
            'http://127.0.0.1:8000/api/%s',
            $model
        );

        $parsedUrl = parse_url($routeBase);

        $items = [
            'name' => ucfirst($model),
        ];

        $endpoints = [
            ['POST', '', $this->getColumnsData($modelData), 'Create '.$model],
            ['PATCH', '/:id', $this->getColumnsData($modelData), 'Update '.$model],
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
                        'raw' => json_encode($data),
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
                        'port' => $parsedUrl['port'],
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

        foreach ($oldItems as $item) {
            if ($item['name'] !== $items['name']) {
                $oldItems[] = $items;
            }
        }

        $newData = json_encode($this->buildPostmanObject(count($oldItems) ? $oldItems : [$items]), JSON_PRETTY_PRINT);
        file_put_contents($laravelAutoCrudPath.'/postman.json', $newData);
    }

    private function getColumnsData(array $modelData): array
    {
        $table = ModelService::getFullModelNamespace($modelData);
        $columns = Schema::getColumnListing($table);
        $data = [];

        // Get database driver
        $driver = DB::connection()->getDriverName();

        foreach ($columns as $column) {
            $isPrimaryKey = false;

            switch ($driver) {
                case 'mysql':
                case 'pgsql':
                    $columnDetails = DB::select('SELECT COLUMN_NAME, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $column]);
                    if (! empty($columnDetails) && isset($columnDetails[0]->COLUMN_KEY)) {
                        $isPrimaryKey = $columnDetails[0]->COLUMN_KEY === 'PRI';
                    }
                    break;

                case 'sqlite':
                    $columnDetails = DB::select("PRAGMA table_info($table)");
                    foreach ($columnDetails as $col) {
                        if ($col->name === $column && $col->pk == 1) {
                            $isPrimaryKey = true;
                            break;
                        }
                    }
                    break;

                case 'sqlsrv':
                    $columnDetails = DB::select("SELECT COLUMN_NAME, COLUMNPROPERTY(object_id(?), COLUMN_NAME, 'IsIdentity') AS is_identity FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?", [$table, $table, $column]);
                    if (! empty($columnDetails) && isset($columnDetails[0]->is_identity)) {
                        $isPrimaryKey = (bool) $columnDetails[0]->is_identity;
                    }
                    break;
            }

            // Exclude primary keys and timestamp fields
            if ($isPrimaryKey || in_array($column, ['created_at', 'updated_at'])) {
                continue;
            }

            $data[$column] = 'value';
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
