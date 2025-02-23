<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Illuminate\Support\Str;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;

class ViewBuilder
{
    public function create(string $modelName): void
    {
        $modelName = HelperService::toSnakeCase(Str::plural($modelName));
        $viewPath = base_path("resources/views/{$modelName}");

        if (! is_dir($viewPath)) {
            mkdir($viewPath, 0755, true);
        }

        foreach (['index', 'create', 'show', 'edit'] as $view) {
            $filePath = "$viewPath/{$view}.blade.php";
            if (! file_exists($filePath)) {
                file_put_contents($filePath, '');
            }
        }
    }
}
