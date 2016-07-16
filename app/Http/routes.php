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

Route::group(['middleware' => 'auth'], function () {
    Route::get('/', function () {
        return view('welcome');
    });
    Route::get('/index/{id}', function () {
        return view('welcome');
    });
});

Route::group(['prefix' => 'v3', 'namespace' => 'V3', 'middleware' => 'auth'], function () {
    Route::get('pricing/index/{mp?}', 'PricingController@index');
    //Route::get('pricing/skuList', 'PricingController@getSkuList');
    Route::get('pricing/info', 'PricingController@getPriceInfo');
    Route::post('listingSku/save', 'ListingSkuManagement@save');
    Route::resource('tracer', 'TracerSkuController');
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

    Route::resource('api/marketplaceProduct', 'MarketplaceProductController');
});

Route::group(['prefix' => 'v2', 'namespace' => 'V2', 'middleware' => 'auth.basic.once'], function () {
    Route::get('pricing/index', 'PricingController@index');
    //Route::get('pricing/skuList', 'PricingController@getSkuList');
    Route::get('pricing/info', 'PricingController@getPriceInfo');
    Route::post('listingSku/save', 'ListingSkuManagement@save');
});

Route::group(['middleware' => 'auth.basic.once'], function () {
    Route::resource('tracer', 'TracerSkuController');
});

Route::group(['middleware' => ['cors']], function () {
    Route::resource('api/v1/marketplaceProduct', 'MarketplaceProductController');
    Route::get('api/v1/marketplaceCategory/marketplace/{id}', 'MarketplaceCategoryController@showTopCategoriesForControlId');
    Route::resource('api/v1/marketplaceCategory', 'MarketplaceCategoryController');
});

Route::auth();
