<?php

namespace DummyPackage;

use Illuminate\Database\Eloquent\Factory as ModelFactory;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{

    /**
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->app->make(ModelFactory::class)->load(__DIR__ . '/../database/factories');
    }

    /**
     * @return void
     */
    public function register()
    {
    }
}
