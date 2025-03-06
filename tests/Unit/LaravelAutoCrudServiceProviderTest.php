<?php

use Illuminate\Support\Facades\Artisan;
use Mrmarchone\LaravelAutoCrud\Console\Commands\GenerateAutoCrudCommand;
use Mrmarchone\LaravelAutoCrud\LaravelAutoCrudServiceProvider;
use Mrmarchone\LaravelAutoCrud\Services\TableColumnsService;

beforeEach(function () {
    $this->app->register(LaravelAutoCrudServiceProvider::class);
});

it('registers the GenerateAutoCrudCommand command', function () {
    expect(Artisan::all())->toHaveKey('auto-crud:generate');
    expect(Artisan::all()['auto-crud:generate'])->toBeInstanceOf(GenerateAutoCrudCommand::class);
});

it('publishes the config file', function () {
    $this->artisan('vendor:publish', ['--tag' => 'auto-crud-config']);
    expect(file_exists(config_path('laravel_auto_crud.php')))->toBeTrue();
});

it('binds TableColumnsService as a singleton', function () {
    // Resolve the service from the container
    $instance1 = app(TableColumnsService::class);
    $instance2 = app(TableColumnsService::class);

    // Ensure it's an instance of TableColumnsService
    expect($instance1)->toBeInstanceOf(TableColumnsService::class);

    // Ensure it returns the same instance (singleton behavior)
    expect($instance1)->toBe($instance2);
});
