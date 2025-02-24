<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;
use Mrmarchone\LaravelAutoCrud\Services\ModelService;

class RequestBuilder extends BaseBuilder
{
    public function create(array $modelData, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'request', 'Http/Requests', 'Request', $overwrite, function ($modelData) {
            return ['{{ data }}' => HelperService::formatArrayToPhpSyntax($this->getRequestData($modelData))];
        });
    }

    private function getRequestData(array $modelData): array
    {
        $table = ModelService::getFullModelNamespace($modelData);
        $columns = Schema::getColumnListing($table);
        $validationRules = [];
        $driver = DB::connection()->getDriverName();

        foreach ($columns as $column) {
            $rules = [];
            $columnType = Schema::getColumnType($table, $column);
            $isPrimaryKey = false;
            $isUnique = false;
            $isNullable = false;
            $maxLength = null;
            $allowedValues = [];

            switch ($driver) {
                case 'mysql':
                    $columnDetails = DB::select("SHOW COLUMNS FROM `$table` WHERE Field = ?", [$column]);

                    if (! empty($columnDetails)) {
                        $columnInfo = $columnDetails[0];
                        $isPrimaryKey = ($columnInfo->Key === 'PRI');
                        $isUnique = ($columnInfo->Key === 'UNI');
                        $isNullable = ($columnInfo->Null === 'YES');
                        $maxLength = isset($columnInfo->Type) && preg_match('/\((\d+)\)/', $columnInfo->Type, $matches) ? $matches[1] : null;

                        if (str_starts_with($columnInfo->Type, 'enum')) {
                            preg_match("/^enum\((.+)\)$/", $columnInfo->Type, $matches);
                            $allowedValues = isset($matches[1]) ? str_getcsv(str_replace("'", "", $matches[1])) : [];
                        }
                    }
                    break;

                case 'pgsql':
                    $columnDetails = DB::select("
                                    SELECT
                                        column_name,
                                        column_default,
                                        is_nullable,
                                        data_type,
                                        character_maximum_length,
                                        udt_name,
                                        (SELECT COUNT(*) > 0 FROM information_schema.table_constraints tc
                                            JOIN information_schema.constraint_column_usage ccu
                                            ON tc.constraint_name = ccu.constraint_name
                                            WHERE tc.table_name = ? AND ccu.column_name = ? AND tc.constraint_type = 'PRIMARY KEY') AS is_primary,
                                        (SELECT COUNT(*) > 0 FROM information_schema.table_constraints tc
                                            JOIN information_schema.constraint_column_usage ccu
                                            ON tc.constraint_name = ccu.constraint_name
                                            WHERE tc.table_name = ? AND ccu.column_name = ? AND tc.constraint_type = 'UNIQUE') AS is_unique
                                    FROM information_schema.columns
                                    WHERE table_name = ? AND column_name = ?",
                        [$table, $column, $table, $column, $table, $column]
                    );

                    if (!empty($columnDetails)) {
                        $columnInfo = $columnDetails[0];
                        $isPrimaryKey = $columnInfo->is_primary;
                        $isUnique = $columnInfo->is_unique;
                        $isNullable = ($columnInfo->is_nullable === 'YES');
                        $maxLength = $columnInfo->character_maximum_length ?? null;

                        if (str_starts_with($columnInfo->udt_name, '_')) {
                            preg_match("/^_(.+)$/", $columnInfo->udt_name, $matches);
                            $allowedValues = isset($matches[1]) ? str_getcsv(str_replace("'", "", $matches[1])) : [];
                        }
                    }
                    break;

                case 'sqlite':
                    $columnDetails = DB::select("PRAGMA table_info($table)");
                    foreach ($columnDetails as $col) {
                        if ($col->name === $column) {
                            $isPrimaryKey = ($col->pk == 1);
                            $isNullable = ($col->notnull == 0);
                            break;
                        }
                    }
                    break;

                case 'sqlsrv':
                    $columnDetails = DB::select("
                        SELECT COLUMN_NAME, COLUMNPROPERTY(object_id(?), COLUMN_NAME, 'IsIdentity') AS is_identity, IS_NULLABLE, DATA_TYPE
                        FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE TABLE_NAME = ? AND COLUMN_NAME = ?", [$table, $table, $column]);

                    if (! empty($columnDetails)) {
                        $columnInfo = $columnDetails[0];
                        $isPrimaryKey = ($columnInfo->is_identity);
                        $isNullable = ($columnInfo->IS_NULLABLE === 'YES');
                    }
                    break;
            }


            // Handle nullable columns
            $rules[] = $isNullable ? 'nullable' : 'required';

            // Handle column types
            switch ($columnType) {
                case 'string':
                case 'char':
                case 'varchar':
                    $rules[] = 'string';
                    if ($maxLength) {
                        $rules[] = 'max:'.$maxLength;
                    }
                    break;

                case 'integer':
                case 'int':
                case 'bigint':
                case 'smallint':
                case 'tinyint':
                    $rules[] = 'integer';
                    if (str_contains($columnType, 'unsigned')) {
                        $rules[] = 'min:0';
                    }
                    break;

                case 'boolean':
                    $rules[] = 'boolean';
                    break;

                case 'date':
                case 'datetime':
                case 'timestamp':
                    $rules[] = 'date';
                    break;

                case 'text':
                case 'longtext':
                case 'mediumtext':
                    $rules[] = 'string';
                    break;

                case 'decimal':
                case 'float':
                case 'double':
                    $rules[] = 'numeric';
                    break;

                case 'enum':
                    if (! empty($allowedValues)) {
                        $rules[] = 'in:'.implode(',', $allowedValues);
                    }
                    break;

                case 'json':
                    $rules[] = 'json';
                    break;

                case 'binary':
                case 'blob':
                    $rules[] = 'string'; // Handle binary data as string for simplicity
                    break;

                default:
                    $rules[] = 'string'; // Default fallback
                    break;
            }

            // Handle unique columns
            if ($isUnique) {
                $rules[] = 'unique:'.$table.','.$column;
            }

            // Exclude primary keys and timestamps
            if ($isPrimaryKey || in_array($column, ['created_at', 'updated_at'])) {
                continue;
            }

            // Add rules to the validation array
            $validationRules[$column] = implode('|', $rules);
        }

        return $validationRules;
    }
}
