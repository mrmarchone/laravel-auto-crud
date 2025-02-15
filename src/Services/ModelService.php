<?php
declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use function Laravel\Prompts\multiselect;

class ModelService
{
    public static function isModelExists(string $modelName): string|null
    {
        return collect(File::allFiles(app_path('Models')))
            ->map(fn($file) => str_replace(app_path('Models') . DIRECTORY_SEPARATOR, '', $file->getRealPath()))
            ->map(fn($file) => str_replace('.php', '', $file))
            ->map(fn($file) => str_replace(['/', '\\'], '/', $file))
            ->filter(function ($file) use ($modelName) {
                $file = explode(DIRECTORY_SEPARATOR, $file);
                $file = end($file);
                return $file === $modelName;
            })
            ->first();
    }

    public static function showModels(): array
    {
        $models = collect(File::allFiles(app_path('Models')))
            ->map(fn($file) => str_replace(app_path('Models') . DIRECTORY_SEPARATOR, '', $file->getRealPath()))
            ->map(fn($file) => str_replace('.php', '', $file))
            ->map(fn($file) => str_replace(['/', '\\'], '/', $file))
            ->filter(function ($file) {
                $model = 'App\\Models\\' . str_replace('/', '\\', $file);
                $model = new $model();
                return $model instanceof Model;
            })
            ->toArray();

        $models = array_values($models);

        return multiselect(label: 'Select your model, use your space-bar to select.', options: $models);
    }

    public static function resolveModelName($modelName): array
    {
        $parts = explode('/', $modelName);
        return [
            'modelName' => array_pop($parts),
            'folders' => implode('/', $parts) ?: null,
            'namespace' => str_replace('/', '\\', implode('/', $parts)) ?: null,
        ];
    }

    public static function getFullModelNamespace($modelData): string
    {
        if ($modelData['namespace']) {
            $modelName = 'App\\Models\\' . $modelData['namespace'] . '\\' . $modelData['modelName'];
        } else {
            $modelName = 'App\\Models\\' . $modelData['modelName'];
        }

        $model = new $modelName();
        if ($model instanceof Model) {
            return (new $modelName)->getTable();
        }

        throw new InvalidArgumentException('Model ' . $modelName . ' does not exist');
    }
}
