<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Repository\MerchantRepository;

class MerchantService
{
    use ApiPlatformTraitService;

    private $merchantRepository;

    public function __construct(MerchantRepository $merchantRepository)
    {
        $this->merchantRepository = $merchantRepository;
    }

    public function all()
    {
        return $this->merchantRepository->all();
    }

    public function balance($request)
    {
        return $this->merchantRepository->balance($request);
    }

    public function exportMerchantBalanceToExcel()
    {
        $cellData[] = [
            'Merchant ID',
            'Balance'
        ];
        $request = new Request;
        $balances = $this->balance($request);
        foreach ($balances as $balance) {
            $cellData[] = [
                $balance->merchant_id,
                $balance->balance
            ];
        }
        $path = storage_path('fulfillment-order-feed/');
        $cellDataArr['MerchantBalance'] = $cellData;
        $fileName = 'Merchant_Balance';
        $excelFile = $this->generateMultipleSheetsExcel($fileName, $cellDataArr, $path);
        return $excelFile["path"].$excelFile["file_name"];
    }
}
