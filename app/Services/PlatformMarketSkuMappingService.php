<?php
namespace App\Services;

use App\Models\MpControl;
use App\Models\MarketplaceSkuMapping;
use App\Models\SellingPlatform;
use App\Models\PlatformBizVar;
use Carbon\Carbon;
use Excel;
use Illuminate\Http\Request;
/**
* 
*/
class PlatformMarketSkuMappingService 
{
	private $platformGroup=array(
    		"amazon"=>"AZ",
    		"lazada"=>"LZ",
            "priceminister"=>"PM",
            "fnac"=>"FN",
            "qoo10"=>"QO",
            "newegg"=>"NE"
        );
	function __construct($stores=null)
	{
		$this->stores=$stores;
	}

	//1 init Marketplace SKU Mapping 
    public function initMarketplaceSkuMapping($fileName="")
    {	
    	if($fileName){
			$filePath = 'storage/marketplace-sku-mapping/'.$fileName;
    	}else{
    		$filePath = 'storage/marketplace-sku-mapping/skus20160804.xlsx';	
    	}
		Excel::load($filePath, function($reader){
		    $sheetItem = $reader->all();
		    $mappingData=null;
		    foreach($sheetItem as $item){
		    	$itemData=$item->toArray();
		    	foreach ($this->stores as $storeName => $store) {
		   			$this->countryCode = strtoupper(substr($storeName, -2));
					$this->platformAccount = strtoupper(substr($storeName, 0, 2));
					$this->marketplaceId = strtoupper(substr($storeName, 0, -2));
					$this->store=$store;
					$this->mpControl=MpControl::where("marketplace_id",$this->marketplaceId)
									->where("country_id",'=',$this->countryCode)
									->where("status",'=','1')
									->first();	
					$insertActive=false;
					if($this->mpControl){
						if($itemData["country_id"]){
			    			if($itemData["country_id"]==$this->countryCode){
			    				$insertActive=true;
				    		}
					    }else{
					    	$insertActive=true;
					    }
					    if($insertActive){
					    	$mappingData=array(
							'marketplace_sku' =>$itemData["marketplace_sku"] ,
							'sku' => $itemData["esg_sku"],
                            'mp_category_id' => $itemData["mp_category_id"],
                            'mp_sub_category_id' => $itemData["mp_sub_category_id"],
                            'delivery_type' => $itemData["delivery_type"],
							'mp_control_id'=>$this->mpControl->control_id,
							'marketplace_id' => $this->marketplaceId,
							'country_id' => $this->countryCode,
							'lang_id'=>'en',
							'currency'=>$this->store['currency']
							);
							$this->firstOrCreateMarketplaceSkuMapping($mappingData);
					    }
			        }
		        }
		    }
		});
    }
 	//2 init Marketplace SKU Mapping 
    public function updateOrCreateSellingPlatform($storeName,$store)
    {
    	$countryCode = strtoupper(substr($storeName, -2));
    	$platformAccount = strtoupper(substr($storeName, 0, 2));
    	$marketplaceId = strtoupper(substr($storeName, 0, -2));
    	$marketplace=strtolower(substr($storeName, 2, -2));
    	$id="AC-".$platformAccount.$this->platformGroup[$marketplace]."-GROUP".$countryCode;
    	$object=array();
		$object['id']=$id;
        $object['type'] ='ACCELERATOR';
        $object['marketplace'] =$marketplaceId;
        $object['merchant_id'] = 'ESG';
        $object['name'] = $store['name']." GROU ".$countryCode;
        $object['description'] = $store['name']." GROU ".$countryCode;
        $object['create_on'] = date("Y-m-d H:i:s");
        $sellingPlatform = SellingPlatform::updateOrCreate(
        	['id' => $id],$object
        );
        return $sellingPlatform;
    }
    //3 init Marketplace SKU Mapping 
    public function updateOrCreatePlatformBizVar($storeName,$store)
    {
    	$countryCode = strtoupper(substr($storeName, -2));
    	$platformAccount = strtoupper(substr($storeName, 0, 2));
    	$marketplaceId = strtoupper(substr($storeName, 0, -2));
    	$marketplace=strtolower(substr($storeName, 2, -2));
    	$sellingPlatformId="AC-".$platformAccount.$this->platformGroup[$marketplace]."-GROUP".$countryCode;
    	$object=array();
		$object['selling_platform_id']=$sellingPlatformId;
        $object['platform_country_id'] =$countryCode;
        $object['dest_country'] = $countryCode;
        $object['platform_currency_id'] =$store["currency"];
        $object['language_id'] = 'en';
        $object['delivery_type'] = 'EXP';
        $object['create_on'] = date("Y-m-d H:i:s");
        $platformBizVar = PlatformBizVar::updateOrCreate(
        	['selling_platform_id' => $sellingPlatformId],$object
        );
        return $platformBizVar;
    }
	//4 init Marketplace SKU Mapping 
    public function firstOrCreateMarketplaceSkuMapping($mappingData)
	{
		$object=array();
		$object['marketplace_sku']=$mappingData['marketplace_sku'];
        $object['sku'] = $mappingData['sku'];
        $object['mp_control_id'] =$mappingData['mp_control_id'];
        $object['marketplace_id'] = $mappingData['marketplace_id'];
        $object['country_id'] = $mappingData['country_id'];
        $object['lang_id'] = $mappingData['lang_id'];
        $object['mp_category_id'] = $mappingData['mp_category_id'];
        $object['mp_sub_category_id'] = $mappingData['mp_sub_category_id'];
        $object['condition'] = 'New';
        $object['delivery_type'] = $mappingData['delivery_type'];;
        $object['currency'] =$mappingData['currency'];
        $object['status'] = 1;
        //$object['create_on'] = date("Y-m-d H:i:s");
       // MarketplaceSkuMapping::firstOrCreate($object);
        $marketplaceSkuMapping = MarketplaceSkuMapping::updateOrCreate(
            [
                'sku' => $mappingData['sku'],
                'marketplace_id' => $mappingData['marketplace_id'],
                'country_id' => $mappingData['country_id']
            ],
            $object
        );
	}

    public function exportLazadaPricingCsv( Request $request)
    {   
        $marketplaceQuery = MarketplaceSkuMapping::join('product', 'product.sku', '=', 'marketplace_sku_mapping.sku')
            ->select('marketplace_sku_mapping.*','product.name as product_name');
        $countryCode = $request->input("country_code");
        if($request->input("all_marketplace")){
            $marketplaceSkuMapping=$marketplaceQuery->where("marketplace_id" ,"like", "%LAZADA")->get();
        }else if(empty($countryCode)){
            $marketplaceId = $request->input("marketplace_id");
            $marketplaceSkuMapping = $marketplaceQuery->where("marketplace_id" ,"=",$marketplaceId)
                ->get();
        }else{
            $marketplaceId = $request->input("marketplace_id");
            $marketplaceSkuMapping = $marketplaceQuery->where("marketplace_id" ,"=",$marketplaceId)
                ->where("country_id" ,"=",$countryCode)
                ->get();
        }

        $cellData[]=array('Marketplace','Country','ESG SKU','SellerSku','QTY','Price','SalePrice','SaleStartDate','SaleEndDate','Name');    
        foreach($marketplaceSkuMapping as $mappingData){
            $cellRow = array(
                "marketplace_id" => $mappingData->marketplace_id,
                "country_id" => $mappingData->country_id,
                "sku" => $mappingData->sku,
                "marketplace_sku" => $mappingData->marketplace_sku,
                "inventory" => $mappingData->inventory,
                "price" => round($mappingData->price * 1.3, 2),
                "saleprice" => $mappingData->price,
                "start_date" => date("Y-m-d"),
                "end_date" => date("Y-m-d",strtotime("+4 year")),
                "name" => $mappingData->product_name,
            );
           $cellData[]=$cellRow;
        }
        //Excel文件导出功能 
        Excel::create('LazadaPricingDetail',function($excel) use ($cellData){
            $excel->sheet('LazadaPricing', function($sheet) use ($cellData){
                $sheet->rows($cellData);
            });
        })->export('csv');
    }
}