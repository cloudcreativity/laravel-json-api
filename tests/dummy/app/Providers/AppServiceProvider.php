<?php

namespace App\Providers;

use App\Entities\SiteRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory as ModelFactory;

class AppServiceProvider extends ServiceProvider
{

    /**
     * @return void
     */
    public function boot()
    {
        config()->set('database.default', 'testbench');
        config()->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('json-api-default', require __DIR__ . '/../../config/json-api-default.php');

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->app->make(ModelFactory::class)->load(__DIR__ . '/../../database/factories');
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SiteRepository::class);
    }

}
