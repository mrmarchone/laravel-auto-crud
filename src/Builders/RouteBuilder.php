<?php
declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Illuminate\Support\Str;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;

class RouteBuilder
{
    public function create(string $modelName, string $controller, string $type): void
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
}
