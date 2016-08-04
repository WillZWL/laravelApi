<?php 

namespace App\Repository\LazadaMws;
/**
* 
*/
class LazadaProductUpdate extends LazadaProductsCore
{
 
  	private $_requestParams=array();

    function __construct($store)
    {
        parent::__construct($store);
        $this->getRequestParams();
    }

    public function submitXmlData($xmlData)
	{
		return parent::curlPostDataToApi($this->_requestParams,$xmlData);
	}

	public function getRequestParams()
	{
		$requestParams = parent::initRequestParams();
		$this->_requestParams=array_merge($this->_requestParams,$requestParams);
        $this->_requestParams["Action"] = "ProductUpdate";
	}


}