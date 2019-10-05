<?php

namespace Shetabit\Admission\Provider;

use Illuminate\Support\ServiceProvider;
use Shetabit\Admission\Contracts\{PermissionInterface, RoleInterface};
use Shetabit\Admission\PermissionRegistrar;

class AdmissionServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @param PermissionRegistrar $permissionLoader
     */
    public function boot(PermissionRegistrar $permissionLoader)
    {
        /**
         * Configurations that needs to be done by user.
         */
        $this->publishes(
            [
                __DIR__ . '/../../config/admission.php' => config_path('admission.php'),
            ],
            'config'
        );

        /**
         * Migrations that needs to be done by user.
         */
        $this->publishes(
            [
                __DIR__.'/../../database/migrations/' => database_path('migrations')
            ],
            'migrations'
        );

        /**
         * Bind Models' contracts
         */
        $this->registerModelBindings();

        /**
         * Register permissions into gate
         */
        $permissionLoader->registerPermissions();

        $this->app->singleton(PermissionRegistrar::class, function ($app) use ($permissionLoader) {
            return $permissionLoader;
        });
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        /**
         * Load default configurations.
         */
        $this->mergeConfigFrom(__DIR__ . '/../../config/admission.php', 'admission');
    }

    /**
     * Bind contracts' related model.
     */
    protected function registerModelBindings()
    {
        $config = $this->app->config['permission.models'];

        $this->app->bind(PermissionInterface::class, $config['permission']);
        $this->app->bind(RoleInterface::class, $config['role']);
    }
}
