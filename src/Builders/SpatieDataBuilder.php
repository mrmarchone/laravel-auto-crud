<?php

declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mrmarchone\LaravelAutoCrud\Services\ModelService;
use Mrmarchone\LaravelAutoCrud\Transformers\SpatieDataTransformer;

class SpatieDataBuilder extends BaseBuilder
{
    private EnumBuilder $enumBuilder;

    public function __construct()
    {
        parent::__construct();
        $this->enumBuilder = new EnumBuilder;
    }

    public function create(array $modelData, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'spatie_data', 'Data', 'Data', $overwrite, function ($modelData) use ($overwrite) {
            $supportedData = $this->getHelperData($modelData, $overwrite);

            return [
                '{{ namespaces }}' => SpatieDataTransformer::convertNamespacesToString($supportedData['namespaces']),
                '{{ data }}' => SpatieDataTransformer::convertDataToString($supportedData['properties']),
            ];
        });
    }

    private function getHelperData(array $modelData, $overwrite = false): array
    {
        $table = ModelService::getFullModelNamespace($modelData);
        $columns = Schema::getColumnListing($table);
        $driver = DB::connection()->getDriverName();
        $properties = [];
        $validationNamespaces = [];
        $validationNamespace = 'use Spatie\LaravelData\Attributes\Validation\{{ validationNamespace }};';

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
                case 'pgsql':
                    $columnDetails = DB::select('SELECT COLUMN_NAME, COLUMN_KEY, IS_NULLABLE, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLUMN_TYPE
                                             FROM INFORMATION_SCHEMA.COLUMNS
                                             WHERE TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $column]);

                    if (! empty($columnDetails)) {
                        $columnInfo = $columnDetails[0];
                        $isPrimaryKey = ($columnInfo->COLUMN_KEY === 'PRI');
                        $isUnique = ($columnInfo->COLUMN_KEY === 'UNI');
                        $isNullable = ($columnInfo->IS_NULLABLE === 'YES');
                        $maxLength = $columnInfo->CHARACTER_MAXIMUM_LENGTH;

                        if (str_starts_with($columnInfo->COLUMN_TYPE, 'enum')) {
                            preg_match("/^enum\((.+)\)$/", $columnInfo->COLUMN_TYPE, $matches);
                            $allowedValues = isset($matches[1]) ? str_getcsv(str_replace("'", '', $matches[1])) : [];
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
                    $columnDetails = DB::select("SELECT COLUMN_NAME, COLUMNPROPERTY(object_id(?), COLUMN_NAME, 'IsIdentity') AS is_identity, IS_NULLABLE, DATA_TYPE
                                             FROM INFORMATION_SCHEMA.COLUMNS
                                             WHERE TABLE_NAME = ? AND COLUMN_NAME = ?", [$table, $table, $column]);

                    if (! empty($columnDetails)) {
                        $columnInfo = $columnDetails[0];
                        $isPrimaryKey = ($columnInfo->is_identity);
                        $isNullable = ($columnInfo->IS_NULLABLE === 'YES');
                    }
                    break;
            }

            if (in_array($column, ['created_at', 'updated_at']) || $isPrimaryKey) {
                continue;
            }

            $validation = '#[{{ validation }}]';
            $property = 'public '.($isNullable ? '?' : '').'{{ type }} $'.$column.';';

            // Handle column types
            switch ($columnType) {
                case 'string':
                case 'char':
                case 'varchar':
                case 'text':
                case 'longtext':
                case 'mediumtext':
                case 'binary':
                case 'blob':
                    $property = str_replace('{{ type }}', 'string', $property);
                    if ($maxLength) {
                        $rules[] = 'Max('.$maxLength.')';
                        $validationNamespaces[] = str_replace('{{ validationNamespace }}', 'Max', $validationNamespace);
                    }
                    break;

                case 'integer':
                case 'int':
                case 'bigint':
                case 'smallint':
                case 'tinyint':
                    $property = str_replace('{{ type }}', 'int', $property);
                    if (str_contains($columnType, 'unsigned')) {
                        $rules[] = 'Min(0)';
                        $validationNamespaces[] = str_replace('{{ validationNamespace }}', 'Min', $validationNamespace);
                    }
                    break;

                case 'boolean':
                    $property = str_replace('{{ type }}', 'bool', $property);
                    break;

                case 'date':
                case 'datetime':
                case 'timestamp':
                    $property = str_replace('{{ type }}', 'Carbon', $property);
                    $rules[] = 'Date';
                    $validationNamespaces[] = str_replace('{{ validationNamespace }}', 'Date', $validationNamespace);
                    $validationNamespaces[] = 'use Carbon\Carbon;';
                    break;

                case 'decimal':
                case 'float':
                case 'double':
                    $property = str_replace('{{ type }}', 'int', $property);
                    $rules[] = 'Numeric';
                    $validationNamespaces[] = str_replace('{{ validationNamespace }}', 'Numeric', $validationNamespace);
                    break;

                case 'enum':
                    if (! empty($allowedValues)) {
                        $enum = $this->enumBuilder->create($modelData, $allowedValues, $overwrite);
                        $enumClass = explode('\\', $enum);
                        $enumClass = end($enumClass);
                        $rules[] = "Enum($enumClass::class)";
                        $property = str_replace('{{ type }}', $enumClass, $property);
                        $validationNamespaces[] = str_replace('{{ validationNamespace }}', 'Enum', $validationNamespace);
                        $validationNamespaces[] = 'use '.$enum.';';
                    }
                    break;

                case 'json':
                    $property = str_replace('{{ type }}', 'array', $property);
                    $rules[] = 'Json';
                    $validationNamespaces[] = str_replace('{{ validationNamespace }}', 'Json', $validationNamespace);
                    break;

                default:
                    $property = str_replace('{{ type }}', 'string', $property);
                    break;
            }

            // Handle unique columns
            if ($isUnique) {
                $rules[] = "Unique('$table', '$column')";
                $validationNamespaces[] = str_replace('{{ validationNamespace }}', 'Unique', $validationNamespace);
            }

            $properties['properties'][$property] = count($rules) ? str_replace('{{ validation }}', implode(', ', $rules), $validation) : '';
        }
        $properties['namespaces'] = array_unique($validationNamespaces);

        return $properties;
    }
}
