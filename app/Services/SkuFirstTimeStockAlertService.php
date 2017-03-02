<?php

namespace App\Services;

use App\Models\Product;

class SkuFirstTimeStockAlertService
{
    use BaseMailService;

    public function sendSkuFirstTimeStockAlertEmail($options =[])
    {
        $prodData = $this->getProductData($options);
        if ($prodData) {
            $header[] = [
                'merchant_id' => 'Merchant ID',
                'brand_name' => 'Brand',
                'sku' => 'ESG SKU',
                'prod_name' => 'Product Name',
                'warehouse_id' => 'Warehouse ID',
                'inventory' => 'Inventory',
                'surplus_quantity' => 'Surplus Quantity',
            ];
            $cellData = array_merge($header, $prodData);
            // dd($cellData);
            $path = storage_path().'/app/SkuFirstTimeStock';
            $fileName = "SkuFirstTimeStockReport";
            $excelFile = $this->createExcelFile($fileName, $path, $cellData);
            if ($excelFile) {
                $subject = "[ESG] Alert, Accelerator SKU First Time Stocks Report";
                $mailResult = $this->sendAttachmentMail(
                    'storemanager@brandsconnect.net',
                    $subject,
                    [
                        'path' => $path,
                        'file_name' => $fileName.'.xlsx'
                    ],
                    'brave.liu@eservicesgroup.com'
                );
                if ($mailResult === true) {
                    $skuCollection = [];
                    foreach ($prodData as $data) {
                        $skuCollection[$data['sku']] = $data['sku'];
                    }

                    if ($skuCollection) {
                        Product::whereIn("sku", $skuCollection)
                            ->where('sku_type', $options['sku_type'])
                            ->where('first_stocks_email', '0')
                            ->update([
                                'first_stocks_email' => 1
                            ]);
                    }
                }
            }
        }
    }

    public function getProductData($options = [])
    {
        $products = Product::join('inventory AS inv', 'inv.prod_sku', '=', 'sku')
            ->with('MerchantProductMapping')
            ->with('Brand')
            ->where('sku_type', $options['sku_type'])
            ->where('first_stocks_email', 0)
            ->where('inv.inventory', '>', 0)
            ->limit(10)
            ->get();

        if (! $products->isEmpty()) {
            $prodData = [];
            foreach ($products as $product) {
                $prodData[] = [
                    'merchant_id' => $product->MerchantProductMapping ? $product->MerchantProductMapping->merchant_id : null,
                    'brand_name' => $product->brand ? $product->brand->brand_name : null,
                    'sku' => $product->sku,
                    'prod_name' => $product->name,
                    'warehouse_id' => $product->warehouse_id,
                    'inventory' => $product->inventory,
                    'surplus_quantity' => $product->surplus_quantity
                ];
            }
            return $prodData;
        }
        return false;
    }
}