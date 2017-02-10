<?php

namespace App\Services;
use App\Models\Product;
use DB;

class SkuListingAlertService
{
    use BaseMailService;

    public function sendSkuListingAlertEmail()
    {
        $path = storage_path(). '/app/SkuListing';
        $fileName = 'AcceleratorSkuListing';
        $excelFile = $this->generateExcelFile($fileName, $path);

        $subject = "[Accelerator] Email Alert For Accelerator SKU Listing";
        $this->sendAttachmentMail(
            'will.zhang@eservicesgroup.com',
            $subject,
            [
                'path' => $path,
                'file_name' => $fileName. '.xlsx'
            ]
        );
    }

    public function getStocksNoListingSkuList()
    {
        $products = $this->getSkuList()->where('msm.listing_status', 'N')->get();
        return $products;
    }

    public function getListedNoInventorySkuList()
    {
        $products = $this->getSkuList()->where('msm.listing_status', 'Y')->where('msm.inventory', '=', '0')->get();
        return $products;
    }

    public function generateExcelFile($fileName, $path)
    {
        $stocksNoListingSkuList = $this->getStocksNoListingSkuList();

        $listedNoInventorySkuList = $this->getListedNoInventorySkuList();

        if ( !$stocksNoListingSkuList->isEmpty() || !$listedNoInventorySkuList->isEmpty()) {
            $cellDataArr = [];
            $cellDataArr['Stocks_But_Not_Listing_Skus'] = [];
            $cellDataArr['Listed_Without_Inventory_Skus'] = [];

            if (!$stocksNoListingSkuList->isEmpty()) {
                $cellDataArr['Stocks_But_Not_Listing_Skus'] = $this->generateExcelCellData($stocksNoListingSkuList);
            }
            if (!$listedNoInventorySkuList->isEmpty()) {
                $cellDataArr['Listed_Without_Inventory_Skus'] = $this->generateExcelCellData($listedNoInventorySkuList);
            }

            $excelFile = \Excel::create($fileName, function($excel) use ($cellDataArr) {
                foreach ($cellDataArr as $key => $cellData) {
                    if ($cellData) {
                        $excel->sheet($key, function($sheet) use ($cellData) {
                            $sheet->rows($cellData);
                        });
                    }
                }
            })->store('xlsx', $path);
            if ($excelFile) {
                return true;
            }
        } else {
            return false;
        }
    }

    private function generateExcelCellData($data)
    {
        $cellDatas = [];
        $cellDatas[] = [
            'Merchant ID',
            'Brand Name',
            'Sku',
            'Name',
            'Inventory',
            'Surplus_quantity',
            'Marketplace List'
        ];

        foreach ($data as $row) {
            $cellDatas[] = [
                $row->merchant_id,
                $row->brand_name,
                $row->sku,
                $row->name,
                $row->inventory,
                $row->surplus_quantity,
                $row->marketplace_list
            ];
        }
        return $cellDatas;
    }

    public function getSkuList()
    {
        $query = Product::leftJoin('merchant_product_mapping AS mpm', 'product.sku', '=', 'mpm.sku')
                           ->leftJoin('brand AS b', 'product.brand_id', '=', 'b.id')
                           ->leftJoin('marketplace_sku_mapping AS msm', 'product.sku', '=', 'msm.sku')
                           ->where('product.sku_type', '1')
                           ->where('product.status', '>', '0')
                           ->where('product.surplus_quantity', '>', '0')
                           ->groupBy('product.sku')
                           ->select(DB::raw("
                                mpm.merchant_id,
                                b.brand_name,
                                product.sku,
                                product.name,
                                msm.inventory,
                                product.surplus_quantity,
                                GROUP_CONCAT(CONCAT_WS('', msm.`marketplace_id`, msm.`country_id`) SEPARATOR ' , ') AS marketplace_list
                            "));
        return $query;
    }
}