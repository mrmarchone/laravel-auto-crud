<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mrmarchone\LaravelAutoCrud\Services\DatabaseValidatorService;

beforeEach(function () {
    $this->service = new DatabaseValidatorService;
});

test('checkDataBaseConnection returns true when connection is successful', function () {
    DB::shouldReceive('connection->getPdo')->once()->andReturn(true);

    expect($this->service->checkDataBaseConnection())->toBeTrue();
});

test('checkDataBaseConnection returns false when connection fails', function () {
    DB::shouldReceive('connection->getPdo')->once()->andThrow(new \PDOException);

    expect($this->service->checkDataBaseConnection())->toBeFalse();
});

test('checkTableExists returns true when table exists', function () {
    Schema::shouldReceive('hasTable')->with('users')->once()->andReturn(true);

    expect($this->service->checkTableExists('users'))->toBeTrue();
});

test('checkTableExists returns false when table does not exist', function () {
    Schema::shouldReceive('hasTable')->with('non_existent_table')->once()->andReturn(false);

    expect($this->service->checkTableExists('non_existent_table'))->toBeFalse();
});
