<?php

namespace App\Services;

use App\User;
use App\Models\StoreWarehouse;
use App\Models\MattelSkuMapping;
use App\Models\PlatformMarketInventory;
use Illuminate\Http\Request;
use Excel;

class PlatformMarketInventoryService
{
    public function getSkuInventorys(Request $request)
    {
        $stores = User::find(\Authorizer::getResourceOwnerId())->stores()->pluck('store_id')->all();
        $query = PlatformMarketInventory::with('MattelSkuMapping')->whereIn('store_id', $stores);
        if ($request->get('mattel_sku')) {
            $query = $query->where('mattel_sku', '=', $request->get('mattel_sku'));
        }
        return $query->paginate(30);
    }

    public function handleUploadFile($fileName = '')
    {
        if (file_exists($fileName)) {
            Excel::selectSheetsByIndex(0)->load($fileName, function ($reader) {
                $sheetItems = $reader->all();
                $sheetItems = $sheetItems->toArray();
                array_filter($sheetItems);
                foreach ($sheetItems as $item) {
                    \DB::beginTransaction();
                    try {
                        $storeId = User::find(\Authorizer::getResourceOwnerId())->stores()->pluck('store_id')->first();
                        $item['store_id'] = $storeId;
                        $this->createPlatformMarketInventory($item);
                        \DB::commit();
                    } catch (\Exception $e) {
                        \DB::rollBack();
                        mail('will.zhang@eservicesgroup.com', 'Platform Market Inventory Upload - Exception', $e->getMessage()."\r\n File: ".$e->getFile()."\r\n Line: ".$e->getLine());
                    }
                }
            });
        }
    }

    public function createPlatformMarketInventory($item = [])
    {
        $object = [];
        $object['store_id'] = $item['store_id'];
        $object['warehouse_id'] = $item['warehouse_id'];
        $object['marketplace_sku'] = $item['marketplace_sku'];
        $object['mattel_sku'] = $item['mattel_sku'];
        $object['dc_sku'] = $item['dc_sku'];
        $object['inventory'] = $item['quantity'];
        $object['threshold'] = $item['threshold'];
        $platformMarketInventory = PlatformMarketInventory::updateOrCreate(
                [
                    'store_id' => $object['store_id'],
                    'warehouse_id' => $object['warehouse_id'],
                    'marketplace_sku' => $object['marketplace_sku'],
                    'mattel_sku' => $object['mattel_sku'],
                ],
                $object
            );
    }

    public function sendLowStockAlert()
    {
        $result = PlatformMarketInventory::with('marketplaceLowStockAlertEmail')
                ->with('merchantProductMapping')
                ->whereColumn('threshold', '>', 'inventory')
                ->get();
        $new_arr = [];
        foreach ($result as $value) {
            $new_arr[$value->store_id][] = $value;
        }
        foreach ($new_arr as $row) {
            $merchant_id = $country_id = $email = $cc_email = $bcc_email = '';
            $message = "This is to inform below Inventory listed has reached its SKU threshold settings\r\n\r\n";
            $message .= "Product Name,  Mattel SKU, ESG SKU, Inventory, Threshold\r\n";

            foreach ($row as $sRow) {
                $email = $sRow->marketplaceLowStockAlertEmail->to_mail;
                $cc_email = $sRow->marketplaceLowStockAlertEmail->cc_mail;
                $bcc_email = $sRow->marketplaceLowStockAlertEmail->bcc_mail;

                $merchant_id = $sRow->merchantProductMapping->merchant_id;
                $country_id = substr($sRow->warehouse_id, -5, 2);

                $message .= $sRow->merchantProductMapping->product->name.";    ".$sRow->mattel_sku.";  ".$sRow->merchantProductMapping->sku.";    ".$sRow->inventory.";   ".$sRow->threshold."\r\n\r\n";
            }

            $message .= "\r\nPlease arrange stock replenishment at your earliest convenience.\r\n\r\n";
            $message .= "Thank you.";
            $subject = $country_id.'_'.$merchant_id." Inventory Report";
            $headers = "From: admin@shop.eservciesgroup.com"."\r\n";
            if ($cc_email) {
                $headers .= "CC:".$cc_email."\r\n";
            }
            if ($bcc_email) {
                $headers .= "BCC:".$bcc_email."\r\n";
            }
            if ($email && $merchant_id && $country_id) {
                mail($email, $subject, $message, $headers);
            }
        }
    }
}