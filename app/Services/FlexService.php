<?php

namespace App\Services;

class FlexService
{
   public function processReport($pmgw, $filename)
   {
       $this->paymentGatewayFactory($pmgw)->processReport($filename);
   }

   public function paymentGatewayFactory($pmgw)
   {
        if(strpos($pmgw,"lazada") !== false)
        {
            $factory = new LazadaGatewayReportService();
        }

        $factory->setPmgw($pmgw);
        return $factory;
   }
}