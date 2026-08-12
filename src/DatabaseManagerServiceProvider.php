<?php

namespace Scry\DatabaseManager;

use Illuminate\Support\ServiceProvider;

class DatabaseManagerServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/database-manager.php',
            'database-manager'
        );

        $this->app->singleton(DatabaseExplorerManager::class, function ($app) {
            return new DatabaseExplorerManager($app);
        });

        $this->app->alias(DatabaseExplorerManager::class, 'database-explorer');
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerResources();
        $this->registerPublishing();
    }

    /**
     * Register package routes.
     */
    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }

    /**
     * Register package resources.
     */
    protected function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'database-manager');
    }

    /**
     * Register publishable assets and config.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/database-manager.php' => config_path('database-manager.php'),
            ], 'database-manager-config');

            $this->publishes([
                __DIR__ . '/../public' => public_path('vendor/database-manager'),
            ], 'database-manager-assets');
        }
    }
}
