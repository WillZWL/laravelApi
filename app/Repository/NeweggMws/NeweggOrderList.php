<?php

namespace App\Repository\NeweggMws;

class NeweggOrderList extends NeweggOrderCore
{
    private $orderNumberList;
    private $orderDateFrom;
    private $orderDateTo;
    private $status;
    private $resourceMethod;
    private $xsdFile = "\OrderMgmt\GetOrderInfo\GetOrderInfoRequest.xsd";
    private $resourceUrl = "ordermgmt/order/orderinfo";

    public function __construct($store)
    {
        parent::__construct($store);

        # set the method for curl
        $this->setResourceMethod("PUT");
    }

    public function fetchOrderList()
    {
        $error_message = "";
        $fetchList["data"] = [];
        $fetchList = $this->fetchOrderByPage(1);
        if($fetchList["allError"]) {
            $error_message = ""; 
            foreach ($fetchList["allError"] as $page => $errors) {
                $error_message .= "\n error: \n" . implode("\n", $errors) . "\n Request Info for IT: " . print_r($fetchList["allRequestInfo"][$page], true) . "\r\n <hr></hr>";
            }

            $subject = "Error - NeweggOrderList::fetchOrderList() ". parent::getStoreName();
            $email = parent::getItAlertEmail();
            if(parent::getUserAlertEmail())
                $email .= ",".implode(",", parent::getUserAlertEmail());

            if(getenv("APP_ENV") != "production") {
                var_dump(__LINE__ . " fetchOrderList error");
                dd($error_message);
            } else {
                parent::sendMail($email, $subject, $error_message);                
            }
            return null;
        }

        return $fetchList["data"];        
    }

    /**
     * Recursively calls itself and append <OrderInfo> of all pages
     */
    private function fetchOrderByPage($pageIndex = 1)
    {
        $returnData = $data = $orderInfo = $allData = $allError = $allRequestInfo = [];
        $requestBody = $this->getRequestBody();
        $requestParams = $this->getRequestParams();
        $result = parent::query($this->getResourceUrl(), $this->getResourceMethod(), $requestParams, $requestBody);

        if($result["error"]) {
            if($pageIndex == 1) {
                $allError[$pageIndex] = $result["error"];
                $allRequestInfo[$pageIndex] = $result["requestInfo"];                
            }
        } else {

            $data = $result["data"];
            if ($data["PageInfo"]["TotalCount"] && is_array($data["OrderInfoList"])) {
                # start with page 1's orders
                $orderInfo = $data["OrderInfoList"];
            }

            # if first time calling this function, check if there are more pages
            if($pageIndex == 1 && $data) {
                if($data["PageInfo"]["TotalPageCount"] > 0) {
                    for ($i=1; $i < $data["PageInfo"]["TotalPageCount"]; $i++) {
                        $nextPage = $i+1;
                        $nextResult = $this->fetchOrderByPage($nextPage);

                        if(is_array($nextResult["data"]["OrderInfoList"]))
                            $orderInfo = array_merge($orderInfo, $nextResult["data"]["OrderInfoList"]);
                        if($nextResult["error"])
                            $allError[$nextPage] = $nextResult["error"];
                        if($nextResult["requestInfo"])
                            $allRequestInfo[$nextPage] = $nextResult["requestInfo"];
                    }                    
                }

                # rewrite OrderInfoList with orders from all pages
                $data["OrderInfoList"]= $orderInfo;
            }
        }

        $returnData = ["data"=>$data,"allError"=>$allError,"allRequestInfo"=>$allRequestInfo,"requestInfo"=>$result["requestInfo"], "error"=>$result["error"]];
        return $returnData;
    }

    protected function getRequestParams()
    {
        $requestParams = ["version"=>"306"];
        return $requestParams;
    }

    protected function getRequestBody($pageIndex=1)
    {
        $requestXml[] = "<NeweggAPIRequest>";
        $requestXml[] = "<OperationType>GetOrderInfoRequest</OperationType>";
        $requestXml[] = "<RequestBody>";
        $requestXml[] = "<PageIndex>{$pageIndex}</PageIndex>";
        $requestXml[] = "<PageSize>100</PageSize>";
        $requestXml[] = "<RequestCriteria>";

        // $this->setOrderNumberList(array("222485619"));
        // this will ignore other search conditions
        if(is_array($this->getOrderNumberList())) {
            $requestXml[] = "<OrderNumberList>";
            foreach ($this->getOrderNumberList() as $key => $orderNumber) {
                $requestXml[] = "<OrderNumber>{$orderNumber}</OrderNumber>";
            }
            $requestXml[] = "</OrderNumberList>";            
        }

        if ($this->getStatus()) {
            $requestXml[] = "<Status>" . $this->getStatus() . "</Status>";
        }
        if ($this->getOrderDateFrom()) {
            $requestXml[] = "<OrderDateFrom>" . $this->getOrderDateFrom() . "</OrderDateFrom>";
        }
        if ($this->getOrderDateTo()) {
            $requestXml[] = "<OrderDateTo>" . $this->getOrderDateTo() . "</OrderDateTo>";
        }
        if ($this->getCountryCode()) {
            $requestXml[] = "<CountryCode>" . $this->getCountryCode() . "</CountryCode>";
        }

        $requestXml[] = "</RequestCriteria>";
        $requestXml[] = "</RequestBody>";
        $requestXml[] = "</NeweggAPIRequest>";
    
        return implode("\n", $requestXml);
    }

    // protected function prepare($data = array())
    // {
    //     if (isset($data['Body']) && isset($data['Body']['Orders']) && isset($data['Body']['Orders']['Order'])) {
    //         return parent::fix($data['Body']['Orders']['Order']);
    //     }

    //     return null;
    // }

    public function getOrderDateFrom()
    {
        return $this->orderDateFrom;
    }

    public function setOrderDateFrom($value)
    {
        $this->orderDateFrom = $value;
    }

    public function getOrderNumberList()
    {
        return $this->orderNumberList;
    }

    public function setOrderNumberList($value)
    {
        $this->orderNumberList = $value;
    }

    public function getOrderDateTo()
    {
        return $this->orderDateTo;
    }

    public function setOrderDateTo($value)
    {
        $this->orderDateTo = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getResourceMethod()
    {
        return $this->resourceMethod;
    }

    public function setResourceMethod($value)
    {
        $this->resourceMethod = $value;
    }

    public function getResourceUrl()
    {
        return $this->resourceUrl;
    }

    public function setResourceUrl($value)
    {
        $this->resourceUrl = $value;
    }
}
