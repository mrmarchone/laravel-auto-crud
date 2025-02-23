<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

use function Laravel\Prompts\multiselect;

class ModelService
{
    public static function isModelExists(string $modelName, string $modelsPath): ?string
    {
        $modelsPath = static::handleModelsPath($modelsPath);

        return collect(File::allFiles(base_path($modelsPath)))
            ->map(function ($file) {
                $content = file_get_contents($file->getRealPath());
                $namespace = '';

                // Extract the namespace from the file
                if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
                    $namespace = trim($matches[1]);
                }

                // Extract the class name (file name without .php)
                $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);

                // Construct the full class namespace
                return $namespace ? $namespace . '\\' . $className : null;
            })
            ->filter(function ($fullNamespace) use ($modelName) {
                if (!$fullNamespace) {
                    return false;
                }

                // Check if the class name matches (without namespace)
                $classParts = explode('\\', $fullNamespace);
                $actualClassName = end($classParts);

                return $actualClassName === $modelName;
            })
            ->first();
    }

    public static function showModels(string $modelsPath): ?array
    {
        $modelsPath = static::handleModelsPath($modelsPath);
        $models = collect(File::allFiles(base_path($modelsPath)))
            ->map(function ($file) {
                $content = file_get_contents($file->getRealPath());
                $namespace = '';

                // Extract namespace
                if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
                    $namespace = trim($matches[1]);
                }

                // Extract class name from file name
                $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);

                // Construct full namespace
                return $namespace ? $namespace . '\\' . $className : null;
            })
            ->filter(function ($fullNamespace) {
                if (!$fullNamespace) {
                    return false;
                }

                // Ensure the class exists and is an instance of Model
                if (!class_exists($fullNamespace)) {
                    return false;
                }

                return is_subclass_of($fullNamespace, Model::class);
            })
            ->values() // Reset array keys
            ->toArray();

        $models = array_values($models);

        return count($models) ? multiselect(label: 'Select your model, use your space-bar to select.', options: $models) : null;
    }

    public static function resolveModelName($modelName): array
    {
        $parts = explode('\\', $modelName);

        return [
            'modelName' => array_pop($parts),
            'folders' => implode('/', $parts) !== 'App/Models' ? implode('/', $parts) : null,
            'namespace' => str_replace('/', '\\', implode('/', $parts)) ?: null,
        ];
    }

    public static function getFullModelNamespace($modelData): string
    {
        if ($modelData['namespace']) {
            $modelName = $modelData['namespace'] . '\\' . $modelData['modelName'];
        } else {
            $modelName = $modelData['modelName'];
        }

        $model = new $modelName;

        if (is_subclass_of($model, Model::class)) {
            return (new $modelName)->getTable();
        }

        throw new InvalidArgumentException('Model ' . $modelName . ' does not exist');
    }

    public static function handleModelsPath(string $modelsPath): string
    {
        return str_ends_with($modelsPath, '/') ? $modelsPath : $modelsPath . DIRECTORY_SEPARATOR;
    }
}
