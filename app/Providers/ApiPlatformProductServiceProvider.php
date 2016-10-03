<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;

class ApiPlatformProductServiceProvider extends ServiceProvider
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
                'amazon' => 'ApiAmazonProductService',
                'lazada' => 'ApiLazadaProductService',
                'priceminister' => 'ApiPriceMinisterProductService',
                'fnac' => 'ApiFnacProductService',
                'tanga' => 'ApiTangaProductService',
                'wish' => 'ApiWishProductService',
                'newegg' => 'ApiNeweggProductService',
            );

    public function register()
    {
        $this->app->call([$this, 'registerApiService']);
    }

    public function registerApiService(Request $request)
    {
        $apiPlatform = strtolower($request->get('api_platform'));
        $this->service = $apiPlatform ? $this->availableServices[$apiPlatform] : 'ApiLazadaProductService';
        //$this->app->bind('App\Contracts\ApiPlatformInterface', "App\Services\\{$this->service}");
        $this->app->bind('App\Services\ApiPlatformProductFactoryService', function ($app, $parameters) {
            //setcommand
            $this->service = $parameters ? $this->availableServices[$parameters['apiName']] : $this->service;

            return new \App\Services\ApiPlatformProductFactoryService($app->make("App\Services\\{$this->service}"));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [\App\Services\ApiPlatformProductFactoryService::class];
    }
}
