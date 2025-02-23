<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;
use Mrmarchone\LaravelAutoCrud\Services\ModelService;

class CURLBuilder
{
    public function create(array $modelData)
    {
        $laravelAutoCrudPath = base_path('laravel-auto-crud');
        if (! file_exists($laravelAutoCrudPath)) {
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
        file_put_contents($laravelAutoCrudPath.'/curl.txt', "====================={$modelData['modelName']}====================="."\n", FILE_APPEND);
        foreach ($endpoints as $endpoint) {
            [$method, $path] = $endpoint;
            $data = $endpoint[2] ?? [];
            $curlCommand = $this->generateCurlCommand($method, $routeBase.$path, $data);
            file_put_contents($laravelAutoCrudPath.'/curl.txt', $curlCommand."\n\n", FILE_APPEND);
        }
        file_put_contents($laravelAutoCrudPath.'/curl.txt', "====================={$modelData['modelName']}====================="."\n", FILE_APPEND);
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
            $curlCommand .= "--data '".$jsonData."'";
        } else {
            $curlCommand .= "--request {$method}";
        }

        return $curlCommand;
    }

    private function getCurlData(array $modelData): array
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
}
