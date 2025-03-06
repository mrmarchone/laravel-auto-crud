<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Illuminate\Support\Str;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;
use Mrmarchone\LaravelAutoCrud\Services\ModelService;
use Mrmarchone\LaravelAutoCrud\Services\TableColumnsService;
use Mrmarchone\LaravelAutoCrud\Traits\TableColumnsTrait;

class CURLBuilder
{
    use TableColumnsTrait;

    public function __construct()
    {
        $this->modelService = new ModelService;
        $this->tableColumnsService = new TableColumnsService;
    }

    public function create(array $modelData): void
    {
        $laravelAutoCrudPath = base_path('laravel-auto-crud');
        if (!file_exists($laravelAutoCrudPath)) {
            mkdir($laravelAutoCrudPath, 0755, true);
        }

        $routeBase = sprintf(
            'http://127.0.0.1:8000/api/%s',
            HelperService::toSnakeCase(Str::plural($modelData['modelName']))
        );
        $endpoints = [
            ['POST', '', $this->getCurlData($modelData)],
            ['PATCH', '/:id', $this->getCurlData($modelData)],
            ['DELETE', '/:id'],
            ['GET', ''],
            ['GET', '/:id'],
        ];
        file_put_contents($laravelAutoCrudPath . '/curl.txt', "====================={$modelData['modelName']}=====================" . "\n", FILE_APPEND);
        foreach ($endpoints as $endpoint) {
            [$method, $path] = $endpoint;
            $data = $endpoint[2] ?? [];
            $curlCommand = $this->generateCurlCommand($method, $routeBase . $path, $data);
            file_put_contents($laravelAutoCrudPath . '/curl.txt', $curlCommand . "\n\n", FILE_APPEND);
        }
        file_put_contents($laravelAutoCrudPath . '/curl.txt', "====================={$modelData['modelName']}=====================" . "\n", FILE_APPEND);
    }

    private function generateCurlCommand(string $method, string $url, array $data = []): string
    {
        $method = strtoupper($method);
        // Base cURL command
        $curlCommand = "curl --location '{$url}' \\\n";
        $curlCommand .= "--header 'Accept: application/json' \\\n";
        $curlCommand .= "--header 'Content-Type: application/json' \\\n";
        // Attach data for methods that require a request body
        if (in_array($method, ['POST', 'PATCH'])) {
            $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $curlCommand .= "--request {$method} \\\n";
            $curlCommand .= "--data '" . $jsonData . "'";
        } else {
            $curlCommand .= "--request {$method}";
        }

        return $curlCommand;
    }

    private function getCurlData(array $modelData): array
    {
        $columns = $this->getAvailableColumns($modelData);
        $data = [];

        foreach ($columns as $column) {
            $columnName = $column['name'];
            $data[$columnName] = 'value';
        }

        return $data;
    }
}
