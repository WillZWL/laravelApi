<?php

namespace App\Services;
use App\Models\Product;

class SkuCreatedAlertService
{
    use BaseMailService;

    public function sendSkuCreatedAlertEmail($options =[])
    {
        $start_date = date('Y-m-d H:i:s', time()-3600*24);
        $end_date = date('Y-m-d H:i:s');
        $sku_type = $options['sku_type'];
        $products = Product::with('MerchantProductMapping')->with('Brand')->where('sku_type', $sku_type)->whereBetween('create_on', [$start_date, $end_date])->get();

        if (! $products->isEmpty() ) {
            $cellDatas = [];
            $cellDatas[] = [
                'Merchant ID',
                'Brand',
                'ESG SKU',
                'Product Name',
                'Surplus QTY'
            ];
            foreach ($products as $product) {
                $cellDatas[] = [
                    $product->MerchantProductMapping->merchant_id,
                    $product->brand->brand_name,
                    $product->sku,
                    $product->name,
                    $product->surplus_quantity
                ];
            }

            $path = storage_path().'/app/SkuCreated';
            $fileName = "NewSkuCreatedList";

            $excelFile = $this->createExcelFile($fileName, $path, $cellDatas);

            $subject = "[Accelerator] Email alert for new Accelerator SKU created";

            $this->sendAttachmentMail(
                'will.zhang@eservicesgroup.com',
                $subject,
                [
                    'path' => $path,
                    'file_name' => $fileName.'.xlsx'
                ]
            );
        }
    }
}