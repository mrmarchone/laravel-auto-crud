<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mrmarchone\LaravelAutoCrud\Services\TableColumnsService;

beforeEach(function () {
    // Mock Schema facade to return test columns
    Schema::shouldReceive('getColumnListing')
        ->once()
        ->with('users')
        ->andReturn(['id', 'name', 'email', 'status', 'password', 'created_at', 'updated_at']);
});

it('returns available columns excluding primary keys and specified columns with mysql', function () {
    // Mock DB facade to return MySQL driver
    DB::shouldReceive('connection')
        ->andReturnSelf()
        ->shouldReceive('getDriverName')
        ->andReturn('mysql');

    DB::shouldReceive('select')
        ->andReturnUsing(function ($query, $bindings) {
            $column = $bindings[0];

            $mockColumns = [
                'id' => (object) ['Key' => 'PRI', 'Null' => 'NO', 'Type' => 'int(11)'],
                'name' => (object) ['Key' => '', 'Null' => 'NO', 'Type' => 'varchar(255)'],
                'email' => (object) ['Key' => '', 'Null' => 'NO', 'Type' => 'varchar(255)'],
                'status' => (object) ['Key' => '', 'Null' => 'YES', 'Type' => "enum('active', 'inactive', 'pending')"],
                'password' => (object) ['Key' => '', 'Null' => 'NO', 'Type' => 'varchar(255)'],
                'created_at' => (object) ['Key' => '', 'Null' => 'YES', 'Type' => 'timestamp'],
                'updated_at' => (object) ['Key' => '', 'Null' => 'YES', 'Type' => 'timestamp'],
            ];

            return isset($mockColumns[$column]) ? [$mockColumns[$column]] : [];
        });

    Schema::shouldReceive('getColumnType')
        ->andReturn('varchar');

    // Mock the service and override getColumnDetails
    $service = new TableColumnsService;

    // Act: Call getAvailableColumns
    $result = $service->getAvailableColumns('users');

    // Assert: Should return only non-primary, non-excluded columns
    expect($result)->toBe([
        [
            'is_primary_key' => false,
            'is_unique' => false,
            'is_nullable' => false,
            'type' => 'varchar',
            'max_length' => '255',
            'allowed_values' => [],
            'name' => 'name',
            'table' => 'users',
        ],
        [
            'is_primary_key' => false,
            'is_unique' => false,
            'is_nullable' => false,
            'type' => 'varchar',
            'max_length' => '255',
            'allowed_values' => [],
            'name' => 'email',
            'table' => 'users',
        ],
        [
            'is_primary_key' => false,
            'is_unique' => false,
            'is_nullable' => true,
            'type' => 'varchar',
            'max_length' => null,
            'allowed_values' => [
                'active',
                ' inactive',
                ' pending',
            ],
            'name' => 'status',
            'table' => 'users',
        ],
        [
            'is_primary_key' => false,
            'is_unique' => false,
            'is_nullable' => false,
            'type' => 'varchar',
            'max_length' => '255',
            'allowed_values' => [],
            'name' => 'password',
            'table' => 'users',
        ],
    ]);
});

it('returns empty columns excluding primary keys and specified columns with mysql', function () {
    // Mock DB facade to return MySQL driver
    DB::shouldReceive('connection')
        ->andReturnSelf()
        ->shouldReceive('getDriverName')
        ->andReturn('mysql');

    DB::shouldReceive('select')
        ->andReturnUsing(function ($query, $bindings) {
            $column = $bindings[0];
            $mockColumns = [];

            return isset($mockColumns[$column]) ? [$mockColumns[$column]] : [];
        });

    Schema::shouldReceive('getColumnType')
        ->andReturn('varchar');

    // Mock the service and override getColumnDetails
    $service = new TableColumnsService;

    // Act: Call getAvailableColumns
    $result = $service->getAvailableColumns('users');

    // Assert: Should return only non-primary, non-excluded columns
    expect($result)->toBe([]);
});

it('returns available columns excluding primary keys and specified columns with postgres', function () {
    // Mock DB facade to return MySQL driver
    DB::shouldReceive('connection')
        ->andReturnSelf()
        ->shouldReceive('getDriverName')
        ->andReturn('pgsql');

    DB::shouldReceive('select')
        ->andReturnUsing(function ($query, $bindings) {
            $column = $bindings[1]; // PostgreSQL query uses second binding for column name

            $mockColumns = [
                'id' => (object) [
                    'column_name' => 'id',
                    'is_nullable' => 'NO',
                    'data_type' => 'integer',
                    'character_maximum_length' => null,
                    'udt_name' => 'int4',
                    'is_primary' => true,
                    'is_unique' => false,
                ],
                'name' => (object) [
                    'column_name' => 'name',
                    'is_nullable' => 'NO',
                    'data_type' => 'character varying',
                    'character_maximum_length' => 255,
                    'udt_name' => 'varchar',
                    'is_primary' => false,
                    'is_unique' => false,
                ],
                'email' => (object) [
                    'column_name' => 'email',
                    'is_nullable' => 'NO',
                    'data_type' => 'character varying',
                    'character_maximum_length' => 255,
                    'udt_name' => 'varchar',
                    'is_primary' => false,
                    'is_unique' => true,
                ],
                'status' => (object) [
                    'column_name' => 'status',
                    'is_nullable' => 'NO',
                    'data_type' => 'USER-DEFINED', // Enum types in PostgreSQL are user-defined
                    'character_maximum_length' => null, // Enum types don't have a character length
                    'udt_name' => '_my_enum_type', // Replace with your actual enum type name
                    'is_primary' => false,
                    'is_unique' => false,
                ],
                'password' => (object) [
                    'column_name' => 'password',
                    'is_nullable' => 'NO',
                    'data_type' => 'character varying',
                    'character_maximum_length' => 255,
                    'udt_name' => 'varchar',
                    'is_primary' => false,
                    'is_unique' => false,
                ],
                'created_at' => (object) [
                    'column_name' => 'created_at',
                    'is_nullable' => 'YES',
                    'data_type' => 'timestamp without time zone',
                    'character_maximum_length' => null,
                    'udt_name' => 'timestamp',
                    'is_primary' => false,
                    'is_unique' => false,
                ],
                'updated_at' => (object) [
                    'column_name' => 'updated_at',
                    'is_nullable' => 'YES',
                    'data_type' => 'timestamp without time zone',
                    'character_maximum_length' => null,
                    'udt_name' => 'timestamp',
                    'is_primary' => false,
                    'is_unique' => false,
                ],
            ];

            return isset($mockColumns[$column]) ? [$mockColumns[$column]] : [];
        });

    Schema::shouldReceive('getColumnType')
        ->andReturn('varchar');

    // Mock the service and override getColumnDetails
    $service = new TableColumnsService;

    // Act: Call getAvailableColumns
    $result = $service->getAvailableColumns('users');

    // Assert: Should return only non-primary, non-excluded columns
    expect($result)->toBe([
        [
            'is_primary_key' => false,
            'is_unique' => false,
            'is_nullable' => false,
            'type' => 'varchar',
            'max_length' => 255,
            'allowed_values' => [],
            'name' => 'name',
            'table' => 'users',
        ],
        [
            'is_primary_key' => false,
            'is_unique' => true,
            'is_nullable' => false,
            'type' => 'varchar',
            'max_length' => 255,
            'allowed_values' => [],
            'name' => 'email',
            'table' => 'users',
        ],
        [
            'is_primary_key' => false,
            'is_unique' => false,
            'is_nullable' => false,
            'type' => 'varchar',
            'max_length' => null,
            'allowed_values' => [
                'my_enum_type',
            ],
            'name' => 'status',
            'table' => 'users',
        ],
        [
            'is_primary_key' => false,
            'is_unique' => false,
            'is_nullable' => false,
            'type' => 'varchar',
            'max_length' => 255,
            'allowed_values' => [],
            'name' => 'password',
            'table' => 'users',
        ],
    ]);
});

it('returns empty columns excluding primary keys and specified columns with postgres', function () {
    // Mock DB facade to return MySQL driver
    DB::shouldReceive('connection')
        ->andReturnSelf()
        ->shouldReceive('getDriverName')
        ->andReturn('pgsql');

    DB::shouldReceive('select')
        ->andReturnUsing(function ($query, $bindings) {
            $column = $bindings[1]; // PostgreSQL query uses second binding for column name

            $mockColumns = [];

            return isset($mockColumns[$column]) ? [$mockColumns[$column]] : [];
        });

    Schema::shouldReceive('getColumnType')
        ->andReturn('varchar');

    // Mock the service and override getColumnDetails
    $service = new TableColumnsService;

    // Act: Call getAvailableColumns
    $result = $service->getAvailableColumns('users');

    // Assert: Should return only non-primary, non-excluded columns
    expect($result)->toBe([]);
});

it('returns available columns excluding primary keys and specified columns with sqlite', function () {
    // Mock DB facade to return MySQL driver
    DB::shouldReceive('connection')
        ->andReturnSelf()
        ->shouldReceive('getDriverName')
        ->andReturn('sqlite');

    DB::shouldReceive('select')
        ->andReturnUsing(function ($query) {
            $mockColumns = [
                (object) ['name' => 'id', 'type' => 'INTEGER', 'notnull' => 1, 'pk' => 1],
                (object) ['name' => 'name', 'type' => 'TEXT', 'notnull' => 1, 'pk' => 0],
                (object) ['name' => 'email', 'type' => 'TEXT', 'notnull' => 1, 'pk' => 0],
                (object) ['name' => 'password', 'type' => 'TEXT', 'notnull' => 1, 'pk' => 0],
                (object) ['name' => 'created_at', 'type' => 'DATETIME', 'notnull' => 0, 'pk' => 0],
                (object) ['name' => 'updated_at', 'type' => 'DATETIME', 'notnull' => 0, 'pk' => 0],
            ];

            return $mockColumns;
        });

    Schema::shouldReceive('getColumnType')
        ->andReturn('varchar');

    // Mock the service and override getColumnDetails
    $service = new TableColumnsService;

    // Act: Call getAvailableColumns
    $result = $service->getAvailableColumns('users');

    // Assert: Should return only non-primary, non-excluded columns
    expect($result)->toBe([
        [
            'is_primary_key' => false,
            'is_unique' => false,
            'is_nullable' => false,
            'type' => 'varchar',
            'max_length' => null,
            'allowed_values' => [],
            'name' => 'name',
            'table' => 'users',
        ],
        [
            'is_primary_key' => false,
            'is_unique' => false,
            'is_nullable' => false,
            'type' => 'varchar',
            'max_length' => null,
            'allowed_values' => [],
            'name' => 'email',
            'table' => 'users',
        ],
        [
            'is_primary_key' => false,
            'is_unique' => false,
            'is_nullable' => false,
            'type' => 'varchar',
            'max_length' => null,
            'allowed_values' => [],
            'name' => 'password',
            'table' => 'users',
        ],
    ]);
});

it('returns empty columns excluding primary keys and specified columns with sqlite', function () {
    // Mock DB facade to return MySQL driver
    DB::shouldReceive('connection')
        ->andReturnSelf()
        ->shouldReceive('getDriverName')
        ->andReturn('sqlite');

    DB::shouldReceive('select')
        ->andReturnUsing(function ($query) {
            $mockColumns = [];

            return $mockColumns;
        });

    Schema::shouldReceive('getColumnType')
        ->andReturn('varchar');

    // Mock the service and override getColumnDetails
    $service = new TableColumnsService;

    // Act: Call getAvailableColumns
    $result = $service->getAvailableColumns('users');

    // Assert: Should return only non-primary, non-excluded columns
    expect($result)->toBe([]);
});

it('returns available columns excluding primary keys and specified columns with sql server', function () {
    // Mock DB facade to return MySQL driver
    DB::shouldReceive('connection')
        ->andReturnSelf()
        ->shouldReceive('getDriverName')
        ->andReturn('sqlsrv');

    DB::shouldReceive('select')
        ->andReturnUsing(function ($query, $bindings) {
            $column = $bindings[2]; // The column name is typically the third binding

            $mockColumns = [
                'id' => (object) [
                    'COLUMN_NAME' => 'id',
                    'DATA_TYPE' => 'int',
                    'IS_NULLABLE' => 'NO',
                    'is_identity' => 1, // Primary key (Identity column)
                ],
                'name' => (object) [
                    'COLUMN_NAME' => 'name',
                    'DATA_TYPE' => 'varchar',
                    'IS_NULLABLE' => 'NO',
                    'is_identity' => 0,
                ],
                'email' => (object) [
                    'COLUMN_NAME' => 'email',
                    'DATA_TYPE' => 'varchar',
                    'IS_NULLABLE' => 'NO',
                    'is_identity' => 0,
                ],
                'password' => (object) [
                    'COLUMN_NAME' => 'password',
                    'DATA_TYPE' => 'varchar',
                    'IS_NULLABLE' => 'NO',
                    'is_identity' => 0,
                ],
                'created_at' => (object) [
                    'COLUMN_NAME' => 'created_at',
                    'DATA_TYPE' => 'datetime',
                    'IS_NULLABLE' => 'YES',
                    'is_identity' => 0,
                ],
                'updated_at' => (object) [
                    'COLUMN_NAME' => 'updated_at',
                    'DATA_TYPE' => 'datetime',
                    'IS_NULLABLE' => 'YES',
                    'is_identity' => 0,
                ],
            ];

            return isset($mockColumns[$column]) ? [$mockColumns[$column]] : [];
        });

    Schema::shouldReceive('getColumnType')
        ->andReturn('varchar');

    // Mock the service and override getColumnDetails
    $service = new TableColumnsService;

    // Act: Call getAvailableColumns
    $result = $service->getAvailableColumns('users');

    // Assert: Should return only non-primary, non-excluded columns
    expect($result)->toBe([
        [
            'is_primary_key' => 0,
            'is_unique' => false,
            'is_nullable' => false,
            'type' => 'varchar',
            'max_length' => null,
            'allowed_values' => [],
            'name' => 'name',
            'table' => 'users',
        ],
        [
            'is_primary_key' => 0,
            'is_unique' => false,
            'is_nullable' => false,
            'type' => 'varchar',
            'max_length' => null,
            'allowed_values' => [],
            'name' => 'email',
            'table' => 'users',
        ],
        [
            'is_primary_key' => 0,
            'is_unique' => false,
            'is_nullable' => false,
            'type' => 'varchar',
            'max_length' => null,
            'allowed_values' => [],
            'name' => 'password',
            'table' => 'users',
        ],
    ]);
});

it('returns empty columns excluding primary keys and specified columns with sql server', function () {
    // Mock DB facade to return MySQL driver
    DB::shouldReceive('connection')
        ->andReturnSelf()
        ->shouldReceive('getDriverName')
        ->andReturn('sqlsrv');

    DB::shouldReceive('select')
        ->andReturnUsing(function ($query, $bindings) {
            $column = $bindings[2]; // The column name is typically the third binding

            $mockColumns = [];

            return isset($mockColumns[$column]) ? [$mockColumns[$column]] : [];
        });

    Schema::shouldReceive('getColumnType')
        ->andReturn('varchar');

    // Mock the service and override getColumnDetails
    $service = new TableColumnsService;

    // Act: Call getAvailableColumns
    $result = $service->getAvailableColumns('users');

    // Assert: Should return only non-primary, non-excluded columns
    expect($result)->toBe([]);
});
