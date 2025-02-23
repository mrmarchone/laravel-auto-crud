<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseValidatorService
{
    public function checkDataBaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function checkTableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
