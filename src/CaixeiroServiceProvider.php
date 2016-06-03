<?php

namespace Artesaos\Caixeiro;

use Artesaos\Caixeiro\Contracts\Driver\Driver;
use Artesaos\Caixeiro\Drivers\MoIP\MoIPDriver;
use Illuminate\Support\ServiceProvider;

/**
 * CaixeiroServiceProvider.
 *
 * Main service provider responsible for detecting and setting up the
 * payments gateway driver.
 */
class CaixeiroServiceProvider extends ServiceProvider
{
    /**
     * Provider's register method.
     */
    public function register()
    {
        // Detect the payment gateway desired driver.
        $this->detectDriver();

        // Configure the detected driver.
        $this->setupDriver();

        // Bind the billable model into the service container.
        $this->bindBillableModel();
    }

    /**
     * Detects the Caixeiro payment gateway driver to be used.
     */
    protected function detectDriver()
    {
        // Detects the driver name from configuration.
        $driverName = config('services.caixeiro.driver');

        // Check if it's one of the supported ones.
        switch ($driverName) {
            // if moip, register MoIPDriver as the payment driver into a singleton.
            case 'moip': {
                $this->app->singleton('caixeiro.driver', function () {
                    return new MoIPDriver();
                });
                break;
            }
        }
    }

    /**
     * Setup the configured driver for it's custom boot methods and calls.
     */
    protected function setupDriver()
    {
        /** @var Driver $driver */
        $driver = app('caixeiro.driver');

        // If there is a detected driver.
        if ($driver) {
            // Call it's setup method.
            $driver->setup();

            // If the driver provides any custom bindings...
            foreach ($driver->bindings() as $abstract => $concrete) {
                // Bind them.
                $this->app->bind($abstract, $concrete);
            }
        }
    }

    /**
     * Detect the billable model and bind into the container.
     */
    protected function bindBillableModel()
    {
        // The configured model class name.
        $model = config('services.caixeiro.model');

        // Bind into the service container.
        $this->app->bind('caixeiro.model', $model);
    }
}
