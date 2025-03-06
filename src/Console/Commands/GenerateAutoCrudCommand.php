<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Console\Commands;

use Illuminate\Console\Command;
use Mrmarchone\LaravelAutoCrud\Services\CRUDGenerator;
use Mrmarchone\LaravelAutoCrud\Services\DatabaseValidatorService;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;
use Mrmarchone\LaravelAutoCrud\Services\ModelService;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\confirm;

class GenerateAutoCrudCommand extends Command
{
    protected $signature = 'auto-crud:generate
    {--MP|model-path= : Set models path.}
    {--M|model=* : Select one or more of your models.}
    {--T|type= : Select weather api or web.}
    {--R|repository : Working with repository design pattern}
    {--O|overwrite : Overwrite the files if already exists.}
    {--P|pattern= : Supports Spatie-Data Pattern.}
    {--C|curl : Generate CURL Requests for API.}
    {--PM|postman : Generate Postman Collection for API.}';

    protected $description = 'A command to create auto CRUD for your models.';

    public function __construct(
        protected DatabaseValidatorService $databaseValidatorService,
        protected CRUDGenerator $CRUDGenerator
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $modelPath = $this->option('model-path') ?? config('laravel_auto_crud.fallback_models_path', 'app/Models/');

        HelperService::displaySignature();

        if (! $this->everythingIsOk()) {
            return;
        }

        $models = [];
        if (count($this->option('model'))) {
            foreach ($this->option('model') as $model) {
                $modelExists = ModelService::isModelExists($model, $modelPath);
                if (! $modelExists) {
                    alert('Model '.$model.' does not exist');

                    continue;
                }
                $models[] = $modelExists;
            }
        } else {
            $models = ModelService::showModels($modelPath);
        }

        if (empty($models)) {
            alert("Can't find models, if the models folder outside app directory , make sure it's already loaded in psr-4.");

            return;
        }

        $this->generate($models);
    }

    private function generate(array $models): void
    {
        foreach ($models as $model) {
            $modelData = ModelService::resolveModelName($model);
            $table = ModelService::getFullModelNamespace($modelData, fn ($modelName) => new $modelName);
            if (! $this->databaseValidatorService->checkTableExists($table)) {
                $createFiles = confirm(
                    label: 'Table '.$table.' not found, Do you want to create empty auto CRUD files?.'
                );
                if (! $createFiles) {
                    alert('Auto CRUD files not generated for model '.$model.'.');

                    continue;
                }
            }
            $this->CRUDGenerator->generate($modelData, $this->options());
        }
    }

    private function everythingIsOk(): bool
    {
        if (! $this->databaseValidatorService->checkDataBaseConnection()) {
            alert('DB Connection Error.');

            return false;
        }

        if ($this->option('type') && ! in_array($this->option('type'), ['api', 'web'])) {
            alert('Make sure that the type is "api" or "web".');

            return false;
        }

        if ($this->option('pattern') == 'spatie-data' && ! class_exists(\Spatie\LaravelData\Data::class)) {
            alert('Make sure that the "spatie-data" package is installed."');

            return false;
        }

        return true;
    }
}
