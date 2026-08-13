<?php

namespace Scry;

use Illuminate\Support\ServiceProvider;
use Scry\Console\Commands\InstallCommand;

class ScryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerResources();
        $this->registerPublishing();
        $this->registerCommands();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/scry.php',
            'scry'
        );

        $this->app->singleton('scry', function () {
            return new Scry();
        });

        $this->app->singleton(DatabaseExplorerManager::class, function ($app) {
            return new DatabaseExplorerManager($app);
        });

        $this->app->alias(DatabaseExplorerManager::class, 'database-explorer');
    }

    /**
     * Register the package routes.
     * Loads API routes before web SPA fallback routes.
     */
    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    /**
     * Register the package resources such as views.
     */
    protected function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'scry');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'database-manager');
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/scry.php' => config_path('scry.php'),
            ], 'scry-config');

            $this->publishes([
                __DIR__ . '/../resources/dist' => public_path('vendor/scry'),
            ], 'scry-assets');
        }
    }

    /**
     * Register the package console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
