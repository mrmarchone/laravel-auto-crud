<?php
declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Mrmarchone\LaravelAutoCrud\Services\CRUDGenerator;
use Mrmarchone\LaravelAutoCrud\Services\DatabaseValidatorService;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;
use Mrmarchone\LaravelAutoCrud\Services\ModelService;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\alert;

class GenerateAutoCrudCommand extends Command
{
    private DatabaseValidatorService $databaseValidatorService;
    private CRUDGenerator $CRUDGenerator;
    protected $signature = 'auto-crud:generate {--M|model=* : Select one or more of your models.} {--T|type= : Select weather api or web.} {--R|repository : Working with repository design pattern} {--O|overwrite : Overwrite the files if already exists.} {--P|pattern= : Supports Spatie-Data Pattern.}';

    protected $description = 'A command to create auto CRUD for your models.';

    public function __construct()
    {
        parent::__construct();
        $this->databaseValidatorService = new DatabaseValidatorService();
        $this->CRUDGenerator = new CRUDGenerator();
    }

    public function handle(): void
    {
        if ($this->option('type') && !in_array($this->option('type'), ['api', 'web'])) {
            alert('Make sure that the type is "api" or "web".');
            return;
        }
        if ($this->option('pattern') == 'spatie-data') {
            if (!class_exists(\Spatie\LaravelData\Data::class)) {
                throw new InvalidArgumentException('Spatie data class not found.');
            }
        }
        HelperService::displaySignature();
        $models = [];
        if (count($this->option('model'))) {
            foreach ($this->option('model') as $model) {
                $modelExists = ModelService::isModelExists($model);
                if (!$modelExists) {
                    alert('Model ' . $model . ' does not exist');
                    continue;
                }
                $models[] = $model;
            }
        } else {
            $models = ModelService::showModels();
        }
        $this->generate($models);
    }

    private function generate(array $models)
    {

        if ($this->databaseValidatorService->checkDataBaseConnection()) {
            foreach ($models as $model) {
                $modelData = ModelService::resolveModelName($model);
                $table = ModelService::getFullModelNamespace($modelData);
                if (!$this->databaseValidatorService->checkTableExists($table)) {
                    $createFiles = confirm(
                        label: 'Table ' . $table . ' not found, Do you want to create empty auto CRUD files?.'
                    );
                    if (!$createFiles) {
                        alert('Auto CRUD files not generated for model ' . $model . '.');
                        continue;
                    }
                }
                $this->CRUDGenerator->generate($modelData, $this->options());
            }
        } else {
            $this->error('DB Connection Error.');
        }
    }
}

