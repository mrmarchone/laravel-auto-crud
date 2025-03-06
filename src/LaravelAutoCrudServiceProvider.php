<?php

namespace Mrmarchone\LaravelAutoCrud;

use Illuminate\Support\ServiceProvider;
use Mrmarchone\LaravelAutoCrud\Console\Commands\GenerateAutoCrudCommand;
use Mrmarchone\LaravelAutoCrud\Services\TableColumnsService;

class LaravelAutoCrudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TableColumnsService::class, function ($app) {
            return new TableColumnsService;
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../Config/laravel_auto_crud.php' => config_path('laravel_auto_crud.php'),
        ], 'auto-crud-config');

        // Boot any package services here
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateAutoCrudCommand::class,
            ]);
        }
    }
}
