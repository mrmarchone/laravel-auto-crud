<?php

namespace Mrmarchone\LaravelAutoCrud;

use Illuminate\Support\ServiceProvider;
use Mrmarchone\LaravelAutoCrud\Console\Commands\GenerateAutoCrudCommand;

class LaravelAutoCrudServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register any bindings or services here
    }

    public function boot()
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
