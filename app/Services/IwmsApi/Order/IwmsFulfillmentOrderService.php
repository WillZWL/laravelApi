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
    private $request;

    public function __construct(FulfillmentOrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function pushFulfillmentOrder()
    {
        try {
            $message = '';
            while( ( $orders = $this->getOrders() ) &&  ( !$orders->getCollection()->isEmpty() ) ) {
                $batchRequest = $this->getNewFulfillmentOrderBatchRequest();
                $jsonData = $this->convertToJsonData($orders);
                $returnPath = $this->saveOrdersToFeedData($jsonData, $batchRequest);
                $batchRequest->request_log = $returnPath;
                $data = json_decode($jsonData);
                $requestData = $data->data;
                $this->initIwmsConfig('', 1);
                $responseData = $this->curlIwmsApi('allocation/save-order', $requestData);
                $message .= $this->processResponseData($batchRequest, $responseData);
            }
            if (trim($message)) {
                mail('will.zhang@eservicesgroup.com', '[IWMS] Push Order TO Iwms Data Validate Error', 'Error Detail : '. $message);
            }
        } catch (\Exception $e) {
            mail('will.zhang@eservicesgroup.com', '[IWMS] PUSH Order To IWMS Failed', 'Error Message'.$e->getMessage());
        }
    }

    public function processResponseData($batchRequest, $responseData = '')
    {
        $message = '';
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
            if (isset($responseData['validate_error']) && !empty($responseData['validate_error'])) {
                $message = 'Batch ID '.$batchRequest->id. "\r\n";
                foreach ($responseData['validate_error'] as $error) {
                    $message .= $error."\r\n";
                }
                $message .= "\r\n<hr>";
            }
        } else {
            throw new \Exception('No Any ResponseData From iWMS');
        }
        $batchRequest->completion_time = date('Y-m-d H:i:s');
        $batchRequest->status = 'C';
        if ($message) {
            $batchRequest->status = 'CF';
        }
        $batchRequest->save();
        return $message;
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

    public function getOrders()
    {
        $request = $this->getNewRequest();
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

    public function getNewRequest()
    {
        if ($this->request === null) {
            $this->request = new Request;
            $this->request->merge([
                'per_page' => 1000,
                'into_iwms_status' => 0
            ]);
        }
        return $this->request;
    }
}
