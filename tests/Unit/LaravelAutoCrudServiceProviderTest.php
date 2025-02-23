<?php

use Mrmarchone\LaravelAutoCrud\LaravelAutoCrudServiceProvider;
use Mrmarchone\LaravelAutoCrud\Console\Commands\GenerateAutoCrudCommand;
use Illuminate\Support\Facades\Artisan;

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
