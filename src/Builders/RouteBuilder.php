<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Illuminate\Support\Str;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;

class RouteBuilder
{
    public function create(string $modelName, string $controller, array $types): void
    {
        $modelName = HelperService::toSnakeCase(Str::plural($modelName));

        if (in_array('api', $types)) {
            $routesPath = base_path('routes/api.php');
            $routeCode = "Route::apiResource('/{$modelName}', {$controller}::class);";
            $this->createRoutes($routesPath, $routeCode);
        }

        if (in_array('web', $types)) {
            $routesPath = base_path('routes/web.php');
            $routeCode = "Route::resource('/{$modelName}', {$controller}::class);";
            $this->createRoutes($routesPath, $routeCode);
        }
    }

    private function createRoutes(string $routesPath, string $routeCode): void
    {
        if (! file_exists($routesPath)) {
            file_put_contents($routesPath, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
        }

        $content = file_get_contents($routesPath);
        if (strpos($content, $routeCode) === false) {
            file_put_contents($routesPath, "\n".$routeCode."\n", FILE_APPEND);
        }
    }
}
