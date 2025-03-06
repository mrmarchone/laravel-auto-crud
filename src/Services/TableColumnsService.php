<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableColumnsService
{
    public function getAvailableColumns(string $table, array $excludeColumns = ['created_at', 'updated_at']): array
    {
        $columns = Schema::getColumnListing($table);

        $driver = DB::connection()->getDriverName();

        $output = [];

        foreach ($columns as $column) {
            $columnDetails = $this->getColumnDetails($driver, $table, $column);

            if (count($columnDetails)) {
                // Exclude primary keys and timestamps
                if ($columnDetails['is_primary_key'] || in_array($column, $excludeColumns)) {
                    continue;
                }

                $output[] = $columnDetails;
            }
        }

        return $output;
    }

    private function getColumnDetails(string $driver, string $table, string $column): array
    {
        return match ($driver) {
            'mysql' => $this->getMySQLColumnDetails($table, $column),
            'pgsql' => $this->getPostgresColumnDetails($table, $column),
            'sqlite' => $this->getSQLiteColumnDetails($table, $column),
            'sqlsrv' => $this->getSQLServerColumnDetails($table, $column),
            default => [],
        };
    }

    private function getMysqlColumnDetails(string $table, string $column): array
    {
        $columnDetails = DB::select("SHOW COLUMNS FROM `$table` WHERE Field = ?", [$column]);

        if (empty($columnDetails)) {
            return [];
        }

        $columnInfo = $columnDetails[0];
        $isPrimaryKey = ($columnInfo->Key === 'PRI');
        $isUnique = ($columnInfo->Key === 'UNI');
        $isNullable = ($columnInfo->Null === 'YES');
        $maxLength = isset($columnInfo->Type) && preg_match('/\((\d+)\)/', $columnInfo->Type, $matches) ? $matches[1] : null;

        if (str_starts_with($columnInfo->Type, 'enum')) {
            preg_match("/^enum\((.+)\)$/", $columnInfo->Type, $matches);
            $allowedValues = isset($matches[1]) ? str_getcsv(str_replace("'", '', $matches[1])) : [];
        }

        return [
            'is_primary_key' => $isPrimaryKey,
            'is_unique' => $isUnique,
            'is_nullable' => $isNullable,
            'type' => Schema::getColumnType($table, $column),
            'max_length' => $maxLength,
            'allowed_values' => $allowedValues ?? [],
            'name' => $column,
            'table' => $table,
        ];
    }

    private function getPostgresColumnDetails(string $table, string $column): array
    {
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

        if (empty($columnDetails)) {
            return [];
        }

        $columnInfo = $columnDetails[0];
        $isPrimaryKey = $columnInfo->is_primary;
        $isUnique = $columnInfo->is_unique;
        $isNullable = ($columnInfo->is_nullable === 'YES');
        $maxLength = $columnInfo->character_maximum_length ?? null;

        if (str_starts_with($columnInfo->udt_name, '_')) {
            preg_match('/^_(.+)$/', $columnInfo->udt_name, $matches);
            $allowedValues = isset($matches[1]) ? str_getcsv(str_replace("'", '', $matches[1])) : [];
        }

        return [
            'is_primary_key' => $isPrimaryKey,
            'is_unique' => $isUnique,
            'is_nullable' => $isNullable,
            'type' => Schema::getColumnType($table, $column),
            'max_length' => $maxLength,
            'allowed_values' => $allowedValues ?? [],
            'name' => $column,
            'table' => $table,
        ];
    }

    private function getSqliteColumnDetails(string $table, string $column): array
    {
        $columnDetails = DB::select("PRAGMA table_info($table)");
        foreach ($columnDetails as $col) {
            if ($col->name === $column) {
                return [
                    'is_primary_key' => $col->pk == 1,
                    'is_unique' => false,
                    'is_nullable' => $col->notnull == 0,
                    'type' => Schema::getColumnType($table, $column),
                    'max_length' => null,
                    'allowed_values' => [],
                    'name' => $column,
                    'table' => $table,
                ];
            }
        }

        return [];
    }

    private function getSQLServerColumnDetails(string $table, string $column): array
    {
        $columnDetails = DB::select(
            "SELECT COLUMN_NAME, COLUMNPROPERTY(object_id(?), COLUMN_NAME, 'IsIdentity') AS is_identity, IS_NULLABLE, DATA_TYPE
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $table, $column]
        );

        if (empty($columnDetails)) {
            return [];
        }

        $columnInfo = $columnDetails[0];

        return [
            'is_primary_key' => $columnInfo->is_identity,
            'is_unique' => false,
            'is_nullable' => $columnInfo->IS_NULLABLE === 'YES',
            'type' => Schema::getColumnType($table, $column),
            'max_length' => null,
            'allowed_values' => [],
            'name' => $column,
            'table' => $table,
        ];
    }
}
