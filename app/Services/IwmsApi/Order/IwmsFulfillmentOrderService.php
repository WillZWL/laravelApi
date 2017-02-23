<?php

namespace App\Services\IwmsApi\Order;

use App\Models\So;
use App\Models\SoExtend;
use App\Models\AllocationBatchRequest;
use App\Repository\FulfillmentOrderRepository;
use App\Services\IwmsApi\IwmsCoreService;
use Illuminate\Http\Request;
use App\Transformers\FulfillmentOrderTransformer;
use League\Fractal;
use League\Fractal\Manager;
use App;

class IwmsFulfillmentOrderService extends IwmsCoreService
{

    public function __construct(FulfillmentOrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function pushFulfillmentOrder()
    {
        try {
            $request = new Request;
            while( ! $this->getOrders($request)->getCollection()->isEmpty() ) {
                $batchRequest = $this->getNewFulfillmentOrderBatchRequest();
                $orders = $this->getOrders($request);
                $jsonData = $this->convertToJsonData($orders);
                $returnPath = $this->saveOrdersToFeedData($jsonData, $batchRequest);
                $batchRequest->request_log = $returnPath;
                $data = json_decode($jsonData);
                $requestData = $data->data;
                $this->initIwmsConfig('', 1);
                $responseData = $this->curlIwmsApi('allocation/save-order', $requestData);
                $this->processResponseData($batchRequest, $responseData);
            }
        } catch (\Exception $e) {
            mail('will.zhang@eservicesgroup.com', '[IWMS] PUSH Order To IWMS Failed', 'Error Message'.$e->getMessage());
        }
    }

    public function processResponseData($batchRequest, $responseData = '')
    {
        $batchRequest->response_log = $responseData;
        if ($responseData) {
            $responseData = json_decode($responseData, true);
            $soNoList = array();
            if (isset($responseData['success_order']) && !empty($responseData['success_order'])) {
                $soNoList = array_merge($soNoList, $responseData['success_order']);
            }
            if (isset($responseData['duplicate_order']) && !empty($responseData['duplicate_order'])) {
                $soNoList = array_merge($soNoList, $responseData['duplicate_order']);
            }
            if (!empty($soNoList)) {
                SoExtend::whereIn('so_no', $soNoList)
                        ->update(['into_iwms_status' => 1]);
            }
        } else {
            throw new \Exception('No Only ResponseData From iWMS');
        }
        $batchRequest->completion_time = date('Y-m-d H:i:s');
        $batchRequest->status = 'C';
        $batchRequest->save();
    }

    public function convertToJsonData($orders)
    {
        $fractal = new Manager();
        $resource = new Fractal\Resource\Collection($orders, new FulfillmentOrderTransformer);
        return $fractal->createData($resource)->toJson();
    }

    public function saveOrdersToFeedData($jsonData, $batchRequest)
    {
        $filePath = \Storage::disk('fulfillmentOrderFeed')->getDriver()->getAdapter()->getPathPrefix().$batchRequest->name."/".date('Y')."/".date('m')."/";
        if (!file_exists($filePath)) {
            mkdir($filePath, 0755, true);
        }
        $fileName = $filePath.'batch_'.$batchRequest->id.'.json';
        file_put_contents($fileName, $jsonData);
        return $fileName;
    }

    public function getOrders(Request $request)
    {
        $request->merge(
            ['per_page' => 10,
             'into_iwms_status' => 0
            ]);
        return $this->orderRepository->getOrders($request);
    }

    public function getNewFulfillmentOrderBatchRequest($name = 'PUSH_ORDER')
    {
        $batch = new AllocationBatchRequest();
        $batch->name = $name;
        $batch->status = 'N';
        $batch->save();
        return $batch;
    }
}
