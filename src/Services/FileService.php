<?php

namespace Mrmarchone\LaravelAutoCrud\Services;

use Illuminate\Support\Facades\File;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\spin;

class FileService
{
    public function createFromStub(array $modelData, string $stubType, string $basePath, string $suffix, ?callable $dataCallback = null): string
    {
        $stubPath = __DIR__ . "/../Stubs/{$stubType}.stub";
        $namespace = 'App\\' . str_replace('/', '\\', $basePath);

        if ($modelData['folders']) {
            $namespace .= '\\' . str_replace('/', '\\', $modelData['folders']);
        }

        $filePath = $this->generateFilePath($modelData, $basePath, $suffix);

        if (file_exists($filePath)) {
            $overwrite = confirm(
                label: ucfirst($stubType) . " file already exists, do you want to overwrite it? " . $filePath,
            );
            if (!$overwrite) {
                return $namespace . '\\' . $modelData['modelName'] . $suffix;
            }
        }

        return spin(
            callback: function () use ($modelData, $stubPath, $filePath, $namespace, $suffix, $dataCallback) {
                File::ensureDirectoryExists(dirname($filePath), 0777, true);

                $data = $dataCallback ? $dataCallback($modelData) : [];
                $content = $this->generateContent($stubPath, $modelData, $namespace, $suffix, $data);

                File::put($filePath, $content);
                info("Created: $filePath");
                return $namespace . '\\' . $modelData['modelName'] . $suffix;
            },
            message: "Creating " . ucfirst($stubType) . "..",
        );
    }

    private function generateFilePath(array $modelData, string $basePath, string $suffix): string
    {
        if ($modelData['folders']) {
            return app_path("{$basePath}/{$modelData['folders']}/{$modelData['modelName']}{$suffix}.php");
        }
        return app_path("{$basePath}/{$modelData['modelName']}{$suffix}.php");
    }

    private function generateContent(string $stubPath, array $modelData, string $namespace, string $suffix, array $data = []): string
    {
        $replacements = [
            '{{ class }}' => $modelData['modelName'] . $suffix,
            '{{ namespace }}' => $namespace,
            ...$data
        ];

        return str_replace(array_keys($replacements), array_values($replacements), file_get_contents($stubPath));
    }
}
