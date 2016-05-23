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
    return view('welcome');
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
    Route::get('pricing/index', 'PricingController@index');
    Route::get('pricing/info', 'PricingController@getPriceInfo');
    Route::get('pricing/skuList', 'PricingController@getSkuList');
    Route::get('pricing/simulate', 'PricingController@simulate');

    Route::get('listingSku/index', 'ListingSkuManagement@index');
    Route::get('listingSku/search', 'ListingSkuManagement@getListing');
    Route::get('listingSku/update', 'ListingSkuManagement@update');
    Route::get('listingSku/getListing', 'ListingSkuManagement@getListing');
    Route::get('listingSku/getData', 'ListingSkuManagement@getData');

    Route::get('listingSku/getCategory', 'ListingSkuManagement@getCategory');
    Route::post('listingSku/add', 'ListingSkuManagement@add');
    Route::post('listingSku/save', 'ListingSkuManagement@save');

    Route::get('amazon/product', 'AmazonProduct@getMatchProductForId');
    Route::get('amazon/getASIN', 'AmazonProduct@getMatchProductForId');
});
