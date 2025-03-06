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
        return self::getAllModels($modelsPath)
            ->filter(function ($fullNamespace) use ($modelName) {
                if (! $fullNamespace) {
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
        $models = self::getAllModels($modelsPath)
            ->filter(function ($fullNamespace) {
                if (! $fullNamespace) {
                    return false;
                }

                // Ensure the class exists and is an instance of Model
                if (! class_exists($fullNamespace)) {
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

    public static function getFullModelNamespace(array $modelData, ?callable $modelFactory = null): string
    {
        if (isset($modelData['namespace']) && $modelData['namespace']) {
            $modelName = $modelData['namespace'].'\\'.$modelData['modelName'];
        } else {
            $modelName = $modelData['modelName'];
        }

        // استخدم الـ Factory إذا تم تمريره، وإلا أنشئ الكائن بالطريقة العادية
        $model = $modelFactory ? $modelFactory($modelName) : new $modelName;

        if (is_subclass_of($model, Model::class)) {
            return $model->getTable();
        }

        throw new InvalidArgumentException("Model {$modelName} does not exist");
    }

    public static function handleModelsPath(string $modelsPath): string
    {
        return str_ends_with($modelsPath, '/') ? $modelsPath : $modelsPath.DIRECTORY_SEPARATOR;
    }

    private static function getAllModels(string $modelsPath): \Illuminate\Support\Collection
    {
        $modelsPath = static::handleModelsPath($modelsPath);

        return collect(File::allFiles(static::getModelNameFromPath($modelsPath)))->map(function ($file) {
            $content = static::getClassContent($file->getRealPath());
            $namespace = '';
            if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
                $namespace = trim($matches[1]);
            }
            $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            return $namespace ? $namespace.'\\'.$className : null;
        });
    }

    private static function getModelNameFromPath(string $modelsPath): string
    {
        return base_path($modelsPath);
    }

    private static function getClassContent($file): false|string
    {
        return File::get($file);
    }
}
