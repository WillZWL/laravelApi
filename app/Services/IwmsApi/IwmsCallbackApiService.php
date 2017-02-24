<?php

namespace App\Services\IwmsApi;

use Illuminate\Http\Request;
use App;

class IwmsCallbackApiService
{
    private $callbackToken = "esg-iwms-123456";
    private $callbackIwmsDeliveryOrderService = null;
    private $callbackIwmsShippedOrderService = null;
    private $callbackIwmsCourierOrderService = null;
    private $callbackIwmsAllocatedOrderService = null;

    use IwmsBaseService;

    public function __construct()
    {
    }

    public function valid(Request $request)
    {
        $echoStr = $request->input("echostr");
        $signature = $request->input("signature");
        $timestamp = $request->input("timestamp");
        $nonce = $request->input("nonce");
        $tmpArr = array($this->callbackToken, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if( $tmpStr == $signature ){
            return $echoStr;
        }
    }

    public function responseMsg(Request $request)
    {
        $echoStr = $request->input("echostr");
        $postContent = $request->getContent();
        //extract post data
        if (!empty($postContent)){
            $postMessage = json_decode($postContent);
            //run the own program jobs
            $responseData = $this->responseMsgAction($postMessage);
            if (isset($responseData)) {
                $responseMsg["responseData"] = $responseData;
            }
            $responseMsg["signature"] = $this->checkSignature($postMessage,$echoStr);
            return $responseMsg;
        }
    }

    public function responseMsgAction($postMessage)
    {
        switch ($postMessage->action) {
            case 'orderCreate':
                return $this->getCallBackDeliveryOrderService()->deliveryOrderCreate($postMessage);
                break;
            case 'confirmShipped':
                return $this->getCallBackShippedOrderService()->deliveryConfirmShipped($postMessage);
                break;
            case 'cancelDelivery':
                return $this->getCallBackDeliveryOrderService()->cancelDeliveryOrder($postMessage);
                break;
            case 'createCourierOrder':
                return $this->getCallBackCourierOrderService()->createCourierOrder($postMessage);
                break;
            case 'cancelCourierOrder':
                return $this->getCallBackCourierOrderService()->cancelCourierOrder($postMessage);
                break;
            case 'createAllocatedOrder':
                return $this->getCallBackAllocatedOrderService()->createAllocatedOrder($postMessage);
                break;
            case 'cancelAllocatedOrder':
                return $this->getCallBackAllocatedOrderService()->cancelAllocatedOrder($postMessage);
                break;
            default:
                break;
        }
    }

    public function checkSignature($postMessage,$echoStr)
    {
        $signatureArr = array();
        foreach ($postMessage->responseMessage as $value) {
            if(isset($value->order_code)){
                $signatureArr[] = $value->order_code;
            }else if(isset($value->receiving_code)){
                $signatureArr[] = $value->receiving_code;
            }
        }
        $signature = implode($signatureArr);
        return base64_encode($this->callbackToken.$signature.$echoStr);
    }

    private function _sendEmail($to, $subject, $message, $header)
    {
        mail("{$to}, brave.liu@eservicesgroup.com, jimmy.gao@eservicesgroup.com", $subject, $message, $header);
    }

    private function getCallBackDeliveryOrderService()
    {
        if ($this->callbackIwmsDeliveryOrderService == null) {
            $this->callbackIwmsDeliveryOrderService = App::make("App\Services\IwmsApi\Callbacks\IwmsDeliveryOrderService");
        }
        return $this->callbackIwmsDeliveryOrderService;
    }

    private function getCallBackShippedOrderService()
    {
        if ($this->callbackIwmsShippedOrderService == null) {
            $this->callbackIwmsShippedOrderService = App::make("App\Services\IwmsApi\Callbacks\IwmsShippedOrderService");
        }
        return $this->callbackIwmsShippedOrderService;
    }

    private function getCallBackCourierOrderService()
    {
        if ($this->callbackIwmsCourierOrderService == null) {
            $this->callbackIwmsCourierOrderService = App::make("App\Services\IwmsApi\Callbacks\IwmsCourierOrderService");
        }
        return $this->callbackIwmsCourierOrderService;
    }

    private function getCallBackAllocatedOrderService()
    {
        if ($this->callbackIwmsAllocatedOrderService == null) {
            $this->callbackIwmsAllocatedOrderService = App::make("App\Services\IwmsApi\Callbacks\IwmsAllocatedOrderService");
        }
        return $this->callbackIwmsAllocatedOrderService;
    }

}

