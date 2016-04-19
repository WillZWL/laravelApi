<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    //return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {
    Route::get('/pricing/{sellingPlatform}/{sku}/{price}', 'PricingController@getPricingInfo');
});

Route::group(['middleware' => ['cors']], function () {
    Route::get('pricing/info', 'PricingController@getPriceInfo');
    Route::get('pricing/skuList', 'PricingController@getSkuList');
    Route::get('pricing/simulate', 'PricingController@simulate');
    Route::post('pricing/save', 'PricingController@save');

    //Route::get('pricing/info', 'PricingController@getPriceInfo');
    //Route::get('/pricing', 'PricingController@index');
    //Route::get('/pricing/list', 'PricingController@getListSku');
    //Route::get('/pricing/calculate', 'PricingController@preCalculateProfit');
    //Route::post('/pricing/save', 'PricingController@save');
});
