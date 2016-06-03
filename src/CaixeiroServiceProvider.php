<?php

namespace Artesaos\Caixeiro;

use Artesaos\Caixeiro\Contracts\Driver\Driver;
use Artesaos\Caixeiro\Drivers\MoIP\MoIPDriver;
use Illuminate\Support\ServiceProvider;

class CaixeiroServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->detectDriver();

        $this->setupDriver();

        $this->bindBillableModel();
    }

    protected function detectDriver()
    {
        $driverName = config('services.caixeiro.driver');

        switch ($driverName) {
            case 'moip': {
                $this->app->singleton('caixeiro.driver', function () {
                    return new MoIPDriver();
                });
                break;
            }
        }
    }

    protected function setupDriver()
    {
        /** @var Driver $driver */
        $driver = app('caixeiro.driver');

        if ($driver) {
            $driver->setup();

            foreach ($driver->bindings() as $abstract => $concrete) {
                $this->app->bind($abstract, $concrete);
            }
        }
    }

    protected function bindBillableModel()
    {
        $model = config('services.caixeiro.model');

        $this->app->bind('caixeiro.model', $model);
    }
}
