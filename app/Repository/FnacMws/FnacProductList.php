<?php

namespace App\Repository\FnacMws;

class FnacProductList extends FnacProductsCore
{
    public function __construct($store)
    {
        parent::__construct($store);
        $this->setFnacAction('offers_query');
    }

    public function fetchProductList()
    {
        $this->setFnacOffersQueryRequestXml();

        return parent::query($this->getRequestXml());
    }

    public function setFnacOffersQueryRequestXml()
    {
        $xmlData = '<?xml version="1.0" encoding="utf-8"?>';
        $xmlData .= '<offers_query results_count="1000" '. $this->getAuthKeyWithToken() .'>';
        $xmlData .=     '<paging>1</paging>';
        $xmlData .=     '<quantity mode="Equals" value="1"/>';
        $xmlData .= '</offers_query>';

        $this->requestXml = $xmlData;
    }

    protected function prepare($data = array())
    {
        if (isset($data['offer'])) {
            return parent::fix($data['offer']);
        }

        return null;
    }

}
