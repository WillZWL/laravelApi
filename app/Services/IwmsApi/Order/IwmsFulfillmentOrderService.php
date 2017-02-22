<?php

namespace App\Services\IwmsApi\Order;

use App\Models\So;
use App\Models\AllocationBatchRequest;
use App\Repository\FulfillmentOrderRepository;
use Illuminate\Http\Request;
use App\Transformers\FulfillmentOrderTransformer;
use Dingo\Api\Routing\Helpers;
use League\Fractal;
use League\Fractal\Manager;
use App;

class IwmsFulfillmentOrderService extends IwmsBaseOrderService
{
    use \App\Services\IwmsApi\IwmsBaseService;

    use Helpers;

    public function __construct(FulfillmentOrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function pushFulfillmentOrder()
    {
        $request = new Request;
        $totalPage = $this->getOrders($request)->lastPage();

        for ($i=1; $i <= $totalPage; $i++) {
            $batchRequest = $this->getNewFulfillmentOrderBatchRequest();
            $orders = $this->getOrders($request, $i);
            $jsonData = $this->convertToJsonData($orders);
            $returnPath = $this->saveOrdersToFeedData($jsonData, $batchRequest);
            $batchRequest->request_log = $returnPath;
            //$responseData = $this->curlIwmsApi('', $jsonData);
            $this->processResponseData($batchRequest);
        }
    }

    //TODO
    public function processResponseData($batchRequest, $responseData = '')
    {
        $batchRequest->response_log = $responseData;
        $batchRequest->completion_time = date('Y-m-d H:i:s');
        $batchRequest->save();
        if ($responseData) {
            //update batchRequest status

            //update so_extend into_wms_status
        }
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

    public function getOrders(Request $request, $page = 1)
    {
        $request->merge(
            ['per_page' => 50,
             'page' => $page,
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
