<?php 
namespace App\Console\Commands;
/**
* 
*/
use Illuminate\Console\Command;
use App;

class BaseApiPlatformCommand extends Command
{
	
	public  $platfromMakert=array('lazada','priceminister');

	public function __construct()
    {
        parent::__construct();
    }

	public function getApiPlatformFactoryService($apiName)
    { 
       return App::make('App\Services\ApiPlatformFactoryService',array("apiName"=>$apiName));
    }
}
