<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ApiLazadaService;
use Illuminate\Http\Request;
use Symfony\Component\Console\Input\ArgvInput;

class ApiPlatformServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */

    protected $availableServices = array(
                'amazon'=>'ApiAmazonService',
                'lazada'=>'ApiLazadaService'
            );

    public function register()
    {
        $this->app->call([$this, 'registerMyService']);
    }

    public function registerMyService(Request $request)
    {
        if($this->app->runningInConsole()) {
            $apiPlatform = (new ArgvInput())->getParameterOption('--api');
        } else{
            $apiPlatform = strtolower($request->get('api_platform'));
        }
        $service = $apiPlatform ? $this->availableServices[$apiPlatform]:'ApiLazadaService';
        $this->app->bind('App\Contracts\ApiPlatformInterface', "App\Services\\{$service}");
    }

}
