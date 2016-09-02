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

    Route::get('platform-market/index', 'PlatformMarketOrderManage@index');
    Route::get('platform-market/transfer-order', 'PlatformMarketOrderManage@transferOrder');
    Route::get('platform-market/product', 'PlatformMarketProductManage@getProductList');
    Route::get('platform-market/update-product-price', 'PlatformMarketProductManage@submitProductPrice');
    Route::resource('platform-market/upload-mapping', 'PlatformMarketProductManage@uploadMarketplacdeSkuMapping');
    Route::resource('platform-market/export-lazada-pricing', 'PlatformMarketProductManage@exportLazadaPricingCsv');
    Route::resource('api/marketplaceProduct', 'MarketplaceProductController');
    Route::get('platform-market/download-xlsx/{file}', 'PlatformMarketProductManage@getMarketplacdeSkuMappingFile');
    Route::get('marketplaceCategory/marketplace/{id}', 'MarketplaceCategoryController@showTopCategoriesForControlId');
    Route::resource('/marketplaceCategory', 'MarketplaceCategoryController');
});

Route::group(['prefix' => 'v2', 'namespace' => 'V2', 'middleware' => 'auth.basic.once'], function () {
    Route::get('pricing/index', 'PricingController@index');
    Route::get('pricing/info', 'PricingController@getPriceInfo');
    Route::post('listingSku/save', 'ListingSkuManagement@save');
});

Route::group(['middleware' => 'auth.basic.once'], function () {
    Route::resource('tracer', 'TracerSkuController');
});

Route::auth();

Route::group(['middleware' => 'cors'], function () {
    Route::post('oauth/access_token', function () {
        return Response::json(Authorizer::issueAccessToken());
    });
});

$api = app('Dingo\Api\Routing\Router');

$api->version('v1', ['namespace' => 'App\Http\Controllers\Api', 'middleware' => ['api.auth', 'cors']], function ($api) {
    $api->resource('marketplace', 'MarketplaceController');
    $api->resource('brand', 'BrandController');
    $api->resource('merchant', 'MerchantController');
    $api->resource('country', 'CountryController');
});

 Route::get('platform/test', 'InterfacePlatformOrder@index');