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
Route::auth();

$api = app('Dingo\Api\Routing\Router');

Route::group(['middleware' => 'cors'], function () {
    Route::post('oauth/access_token', function () {
        return Response::json(Authorizer::issueAccessToken());
    });
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

Route::group(['middleware' => 'auth'], function () {
    Route::get('/', function () {
        return view('welcome');
    });
});

Route::group(['prefix' => '/scout', 'middleware' => 'auth'], function () {
    Route::get('/{vue_route?}', function () {
        return view('scout');
    })->where('vue_route', '[\/\w\.-]*');
});

Route::group(['middleware' => 'auth.basic.once'], function () {
    Route::resource('tracer', 'TracerSkuController');
});

Route::group(['prefix' => 'v2', 'namespace' => 'V2', 'middleware' => 'auth.basic.once'], function () {
    Route::get('pricing/index', 'PricingController@index');
    Route::get('pricing/info', 'PricingController@getPriceInfo');
    Route::post('listingSku/save', 'ListingSkuManagement@save');
});

Route::group(['middleware' => ['cors']], function () {
    Route::get('pricing/simulate', 'PricingController@simulate');
    Route::get('amazon/getASIN', 'AmazonProduct@getMatchProductForId');
    Route::get('listingSku/index', 'ListingSkuManagement@index');
    Route::resource('/marketplaceCategory', 'MarketplaceCategoryController');
    Route::get('marketplaceCategory/marketplace/{id}', 'MarketplaceCategoryController@showTopCategoriesForControlId');
    Route::get('listingSku/getCategory', 'ListingSkuManagement@getCategory');
    Route::post('listingSku/add', 'ListingSkuManagement@add');
    Route::get('listingSku/getData', 'ListingSkuManagement@getData');

    Route::get('platform-market/product', 'PlatformMarketProductManage@getProductList');
    Route::get('platform-market/update-product-price', 'PlatformMarketProductManage@submitProductPrice');
    Route::resource('platform-market/upload-mapping', 'PlatformMarketProductManage@uploadMarketplacdeSkuMapping');
    Route::resource('platform-market/export-lazada-pricing', 'PlatformMarketProductManage@exportLazadaPricingCsv');
    Route::get('platform-market/download-xlsx/{file}', 'PlatformMarketProductManage@getMarketplacdeSkuMappingFile');
    Route::get('lazada-api/donwload-label/{file}', 'Api\Marketplace\LazadaApiController@donwloadLazadaLabelFile');
    Route::get('product-upload/donwload-example-file/{file}', 'Api\ProductUploadController@donwloadExampleFile');
});

$api->version('v1', ['namespace' => 'App\Http\Controllers\Api', 'middleware' => ['api.auth', 'cors']], function ($api) {
    $api->resource('marketplace', 'MarketplaceController');
    $api->resource('brand', 'BrandController');
    $api->resource('merchant', 'MerchantController');
    $api->resource('supplier', 'SupplierController');
    $api->resource('country', 'CountryController');
    $api->resource('colour', 'ColourController');
    $api->resource('version', 'VersionController');
    $api->resource('category', 'CategoryController');
    $api->resource('hscode_category', 'HscodeCategoryController');
    $api->get('marketplace-product/search', 'MarketplaceProductController@search');
    $api->get('marketplace-product/estimate', 'MarketplaceProductController@estimate');
    $api->post('marketplace-product/bulk-update', 'MarketplaceProductController@bulkUpdate');
    $api->post('marketplace-product/add-update', 'MarketplaceProductController@addOrUpdate');
    $api->resource('marketplace-product', 'MarketplaceProductController');
    $api->resource('lazada-api/ready-to-ship', 'Marketplace\LazadaApiController@esgOrderReadyToShip');
    $api->post('product-upload', 'ProductUploadController@upload');
    $api->get('product-upload', 'ProductUploadController@index');
});

Route::get('platform/test', 'InterfacePlatformOrder@index');
