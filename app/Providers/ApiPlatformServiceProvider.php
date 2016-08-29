<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ApiLazadaService;
use Illuminate\Http\Request;

class ApiPlatformServiceProvider extends ServiceProvider
{
    protected $defer = true;
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
    }

    /**
     * Register the application services.
     */
    protected $availableServices = array(
        'amazon' => 'ApiAmazonService',
        'lazada' => 'ApiLazadaService',
        'priceminister' => 'ApiPriceMinisterService',
        'tanga' => 'ApiTangaService',
        'fnac' => 'ApiFnacService',
    );

    public function register()
    {
        $this->app->call([$this, 'registerMyService']);
    }

    public function registerMyService(Request $request)
    {
        $apiPlatform = strtolower($request->get('api_platform'));
        $this->service = $apiPlatform ? $this->availableServices[$apiPlatform] : 'ApiLazadaService';
        //$this->app->bind('App\Contracts\ApiPlatformInterface', "App\Services\\{$this->service}");
        $this->app->bind('App\Services\ApiPlatformFactoryService', function ($app, $parameters) {
            //setcommand

            $this->service = $parameters ? $this->availableServices[$parameters['apiName']] : $this->service;

            return new \App\Services\ApiPlatformFactoryService($app->make("App\Services\\{$this->service}"));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [\App\Services\ApiPlatformFactoryService::class];
    }
}
