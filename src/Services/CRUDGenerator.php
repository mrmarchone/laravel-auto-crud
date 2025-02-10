<?php
declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class CRUDGenerator
{
    private FileService $fileService;

    public function __construct()
    {
        $this->fileService = new FileService();
    }

    public function generate($modelData): void
    {
        $this->generateFiles($modelData);
        info('Auto CRUD files generated successfully');
    }

    private function generateFiles(array $modelData): void
    {
        $checkForType = $this->askControllerType();
        $resourceName = $this->createResourceFromStub($modelData);
        $requestName = $this->createRequestFromStub($modelData);
        if ($checkForType === 'api') {
            $controllerName = $this->createAPIControllerFromStub($modelData, $resourceName, $requestName);
            $this->generateCurlFile($modelData);
        } elseif ($checkForType === 'web') {
            $controllerName = $this->createWebControllerFromStub($modelData, $resourceName, $requestName);
            $this->generateViews($modelData['modelName']);
        }
        $this->generateRoutes($modelData['modelName'], $controllerName, $checkForType);
    }

    private function createResourceFromStub(array $modelData): string
    {
        return $this->fileService->createFromStub($modelData, 'resource', 'Http/Resources', 'Resource', function ($modelData) {
            return ["{{ data }}" => HelperService::formatArrayToPhpSyntax($this->getResourcesData($modelData), true)];
        });
    }

    private function createRequestFromStub(array $modelData): string
    {
        return $this->fileService->createFromStub($modelData, 'request', 'Http/Requests', 'Request', function ($modelData) {
            return ["{{ data }}" => HelperService::formatArrayToPhpSyntax($this->getRequestData($modelData))];
        });
    }

    private function createAPIControllerFromStub(array $modelData, string $resource, string $request): string
    {
        return $this->fileService->createFromStub($modelData, 'api.controller', 'Http/Controllers/API', 'Controller', function ($modelData) use ($resource, $request) {
            $model = $modelData['namespace'] ? 'App\\Models\\' . $modelData['namespace'] . '\\' . $modelData['modelName'] : 'App\\Models\\' . $modelData['modelName'];
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

    private function createWebControllerFromStub(array $modelData, string $resource, string $request): string
    {
        return $this->fileService->createFromStub($modelData, 'web.controller', 'Http/Controllers', 'Controller', function ($modelData) use ($resource, $request) {
            $model = $modelData['namespace'] ? 'App\\Models\\' . $modelData['namespace'] . '\\' . $modelData['modelName'] : 'App\\Models\\' . $modelData['modelName'];
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
                '{{ viewPath }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
                '{{ modelPlural }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
                '{{ routeName }}' => HelperService::toSnakeCase(Str::plural($modelData['modelName'])),
            ];
        });
    }

    private function getResourcesData(array $modelData): array
    {
        $table = ModelService::getFullModelNamespace($modelData);
        $columns = Schema::getColumnListing($table);
        $data = [];
        foreach ($columns as $column) {
            $data["$column"] = '$this->' . $column;
        }
        return $data;
    }

    private function getRequestData(array $modelData): array
    {
        $table = ModelService::getFullModelNamespace($modelData);
        $columns = Schema::getColumnListing($table);
        // Initialize an empty array to hold the validation rules
        $validationRules = [];

        // Loop through each column and generate validation rules
        foreach ($columns as $column) {
            $columnType = Schema::getColumnType($table, $column);
            $columnDetails = DB::select("SHOW COLUMNS FROM `$table` LIKE '$column'")[0];
            // Initialize the rule array for the current column
            $rules = [];

            // Handle nullable columns
            if ($columnDetails->Null === 'YES') {
                $rules[] = 'nullable';
            } else {
                $rules[] = 'required';
            }

            // Handle column types
            switch ($columnType) {
                case 'string':
                case 'char':
                case 'varchar':
                    $rules[] = 'string';
                    if (isset($columnDetails->Length)) {
                        $rules[] = 'max:' . $columnDetails->Length;
                    }
                    break;
                case 'integer':
                case 'int':
                case 'bigint':
                case 'smallint':
                case 'tinyint':
                    $rules[] = 'integer';
                    if (str_contains($columnDetails->Type, 'unsigned')) {
                        $rules[] = 'min:0';
                    }
                    break;
                case 'boolean':
                    $rules[] = 'boolean';
                    break;
                case 'date':
                case 'datetime':
                case 'timestamp':
                    $rules[] = 'date';
                    break;
                case 'text':
                case 'longtext':
                case 'mediumtext':
                    $rules[] = 'string';
                    break;
                case 'decimal':
                case 'float':
                case 'double':
                    $rules[] = 'numeric';
                    break;
                case 'enum':
                    $allowedValues = explode("','", substr($columnDetails->Type, 6, -2));
                    $rules[] = 'in:' . implode(',', $allowedValues);
                    break;
                case 'set':
                    $allowedValues = explode("','", substr($columnDetails->Type, 5, -2));
                    $rules[] = 'in:' . implode(',', $allowedValues);
                    break;
                case 'json':
                    $rules[] = 'json';
                    break;
                case 'binary':
                case 'blob':
                    $rules[] = 'string'; // Handle binary data as string for simplicity
                    break;
                default:
                    $rules[] = 'string'; // Default fallback
                    break;
            }

            // Handle unique columns
            if ($columnDetails->Key === 'UNI') {
                $rules[] = 'unique:' . $table . ',' . $column;
            }

            // Handle primary key columns (usually auto-increment and not required in validation)
            if ($columnDetails->Key === 'PRI' || $column == 'created_at' || $column == 'updated_at') {
                continue;
            }

            // Add the rules to the validation rules array
            $validationRules[$column] = implode('|', $rules);
        }

        return $validationRules;
    }

    private function generateRoutes(string $modelName, string $controller, string $type): void
    {
        $modelName = HelperService::toSnakeCase(Str::plural($modelName));
        $isApi = $type === 'api';

        $routesPath = base_path($isApi ? 'routes/api.php' : 'routes/web.php');
        $routeCode = $isApi
            ? "Route::apiResource('/{$modelName}', {$controller}::class);"
            : "Route::resource('/{$modelName}', {$controller}::class);";

        if (!file_exists($routesPath)) {
            file_put_contents($routesPath, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
        }

        $content = file_get_contents($routesPath);
        if (strpos($content, $routeCode) === false) {
            file_put_contents($routesPath, "\n" . $routeCode . "\n", FILE_APPEND);
        }
    }

    private function generateViews(string $modelName): void
    {
        $modelName = HelperService::toSnakeCase(Str::plural($modelName));
        $viewPath = base_path("resources/views/{$modelName}");

        if (!is_dir($viewPath)) {
            mkdir($viewPath, 0755, true);
        }

        foreach (['index', 'create', 'show', 'edit'] as $view) {
            $filePath = "$viewPath/{$view}.blade.php";
            if (!file_exists($filePath)) {
                file_put_contents($filePath, '');
            }
        }
    }

    private function askControllerType(): string
    {
        return text(
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

    private function generateCurlFile(array $modelData)
    {
        $laravelAutoCrudPath = base_path("laravel-auto-crud");
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

    private function getCurlData(array $modelData): array
    {
        $table = ModelService::getFullModelNamespace($modelData);
        $columns = Schema::getColumnListing($table);
        // Initialize an empty array to hold the validation rules
        $data = [];

        // Loop through each column and generate validation rules
        foreach ($columns as $column) {
            $columnDetails = DB::select("SHOW COLUMNS FROM `$table` LIKE '$column'")[0];
            // Handle primary key columns (usually auto-increment and not required in validation)
            if ($columnDetails->Key === 'PRI' || $column == 'created_at' || $column == 'updated_at') {
                continue;
            }

            $data[$column] = 'value';
        }

        return $data;
    }
}
