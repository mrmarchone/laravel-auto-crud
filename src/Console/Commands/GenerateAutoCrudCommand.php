<?php
declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Console\Commands;

use Illuminate\Console\Command;
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
    protected $signature = 'auto-crud:generate';
    protected $description = 'A command to create auto CRUD for your models.';

    public function __construct()
    {
        parent::__construct();
        $this->databaseValidatorService = new DatabaseValidatorService();
        $this->CRUDGenerator = new CRUDGenerator();
    }

    public function handle(): void
    {
        HelperService::displaySignature();
        $model = ModelService::showModels();
        $modelData = ModelService::resolveModelName($model);
        if ($this->databaseValidatorService->checkDataBaseConnection()) {
            if ($this->databaseValidatorService->checkTableExists(ModelService::getFullModelNamespace($modelData))) {
                $this->CRUDGenerator->generate($modelData);
            } else {
                $createFiles = confirm(
                    label: 'Table not found, Do you want to create empty auto CRUD files?.'
                );
                if ($createFiles) {
                    $this->CRUDGenerator->generate($modelData);
                } else {
                    alert('Auto CRUD files not generated.');
                }
            }
        } else {
            $this->error('DB Connection Error.');
        }
    }
}

