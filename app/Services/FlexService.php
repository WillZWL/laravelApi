<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Config;
use App\Models\FlexRia;
use App\Models\FlexSoFee;
use App\Models\FlexRefund;
use App\Models\FlexGatewayFee;
use App\Models\So;
use DB;
use Excel;
use Zipper;

// use App\Models\So;

class FlexService
{
   public function processReport($pmgw, $filename ,$email)
   {
       return $this->paymentGatewayFactory($pmgw)->processReport($filename,$email);
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


   public function generateFeedbackReport(Request $request)
   {
        $batchIdList = $request->batchIdList;
        $pmgw = $request->pmgw;
        if(strpos($pmgw,"amazon") !== false)
        {
            return $this->generateAmazonFeedbackReport($request);
        }
        elseif(strpos($pmgw,"priceminister") !== false)
        {
            return $this->generatePriceministerFeedbackReport($request);
        }
        elseif(strpos($pmgw,"lazada") !== false)
        {
            return $this->generateLazadaFeedbackReport($request);
        }
   }


   public function generateAmazonFeedbackReport(Request $request)
   {
        $batchIdList = $request->batchIdList;
        $pmgw = $request->pmgw;

        $feedbackReport = array();

        $receiptFeeStatus = ['A_FBA_H','A_FBA_P','A_FBA_W','A_SC','A_CBF','A_FPUFF','A_FPOFF','A_FWBF','A_FTF','A_RFOIP','A_SH','A_SO_FEE','A_OOS','A_OO','A_OPS','A_OP'];

        $cellData[] = ["Merchant ID","SO Number","Amazon ID","Flex Batch ID","Gateway ID","Txn Time","Currency ID","Amount","Commission Fees", "Receipt fees","Refund Others","Others","FBA fees","Subtotal"];
        //flex ria
        $flexRia = new FlexRia();
        $flexRiaList = $flexRia->soFee($batchIdList);
        
        $soList = [];
        if($flexRiaList->count())
        {
          foreach($flexRiaList as $flex)
          {
            $soList[$flex->so_no.$flex->txn_time][] = $flex;
          }
        }
        if(count($soList) > 0)
        {
          foreach ($soList as $so_no => $so)
          {
              $commissionFees = 0;
              $receiptFees = 0;
              foreach ($so as $fee)
              {
                  if(in_array($fee->fee_status,["A_COMM"]))
                  {
                    $commissionFees += $fee->fee_amount;
                  }
                  if(in_array($fee->fee_status,$receiptFeeStatus))
                  {
                    $receiptFees += $fee->fee_amount;
                  }
              }
              $fee->commissionFees = $commissionFees;
              $fee->receiptFees = $receiptFees;
              $fee->subTotal = $fee->amount + $commissionFees + $receiptFees;

              $feedbackReport[$fee->so_no.$fee->txn_time] = $fee;
              //$cellData[] = [$fee->merchant_id,$fee->so_no,$fee->txn_id,$fee->flex_batch_id,$fee->gateway_id,$fee->txn_time,$fee->currency_id,$fee->amount,$fee->commissionFees ==0 ?'':$fee->commissionFees,$fee->receiptFees==0?'':$fee->receiptFees,'','','',$fee->subTotal];
          }
        }

        //flex refund
        $flexRefund = new FlexRefund;
        $flexRefundList = $flexRefund->soFee($batchIdList);
        $refundList = [];
        if($flexRefundList->count() )
        {
          foreach ($flexRefundList as $flex) {
              $refundList[$flex->so_no.$flex->txn_time][] = $flex;
          }
        }
        if(count($refundList)>0)
        {
          foreach ($refundList as $so_no =>$so)
          {
              $commissionFees = 0;
              $refundOthers = 0;
              foreach ($so as $fee)
              {
                  if(in_array($fee->fee_status,["A_RF_RC","A_RF_C"]))
                  {
                    $commissionFees += $fee->fee_amount;
                  }
                  if(in_array($fee->fee_status,['A_RF_SC','A_RF_RFOIP','A_RF_RAF','A_RF','A_RO_S','A_RO','A_RPS','A_RP']))
                  {
                    $refundOthers += $fee->fee_amount;
                  }
              }
              $refund = $fee;
              $subTotal = $refund->amount + $commissionFees + $refundOthers;

              $refund->commissionFees = $commissionFees;
              $refund->refundOthers = $refundOthers;
              $refund->subTotal = $subTotal;

              if(array_key_exists($refund->so_no.$refund->txn_time, $feedbackReport))
              {
                  $feedbackReport["R".$refund->so_no.$refund->txn_time] = $refund;//so have receipt order ,also have refund order
              }
              else
              {
                  $feedbackReport[$refund->so_no.$refund->txn_time] = $refund;
              }
          }
        }

        //gateway fee
        $flexGatewayFeeList = FlexGatewayFee::whereIn("flex_batch_id",$batchIdList)->get();
        $gatewayNum = 1;
        if($flexGatewayFeeList->count())
        {
          foreach ($flexGatewayFeeList as $gatewayFee) {
              if( strpos($gatewayFee->status,"A_S") !== false)
              {
                  $gatewayFee->others = $gatewayFee->amount;
              }
              if( strpos($gatewayFee->status,"A_O") !== false)
              {
                  $gatewayFee->fbaFees = $gatewayFee->amount;
              }
              $gatewayFee->amount = null;
              if($gatewayFee->txn_id)
              {
                $so = So::where(["platform_order_id"=>$gatewayFee->txn_id,"platform_group_order"=>1])->with("sellingPlatform")->first();
                if($so)
                {
                  if(array_key_exists($so->so_no.$gatewayFee->txn_time, $feedbackReport))
                  {
                    $feedbackReport[$so->so_no.$gatewayFee->txn_time]->others = $gatewayFee->others;
                    $feedbackReport[$so->so_no.$gatewayFee->txn_time]->fbaFees = $gatewayFee->fbaFees;
                    $feedbackReport[$so->so_no.$gatewayFee->txn_time]->subTotal = $feedbackReport[$so->so_no.$gatewayFee->txn_time]->subTotal + $gatewayFee->others +$gatewayFee->fbaFees;
                  }
                  else
                  {
                    $gatewayFee->merchant_id = $so->sellingPlatform->merchant_id;
                    $gatewayFee->so_no = $so->so_no;
                    $feedbackReport["gateway".$gatewayNum++] = $gatewayFee;  
                  }
                }
                else
                {
                  //$gatewayFee->txn_id = null;
                  $feedbackReport["gateway".$gatewayNum++] = $gatewayFee;
                }
              }
              else
              {
                $feedbackReport["gateway".$gatewayNum++] = $gatewayFee;
              }
              //$cellData[] = ['','','',$gatewayFee->flex_batch_id,$gatewayFee->gateway_id,$gatewayFee->txn_time,$gatewayFee->currency_id,'','','','',$gatewayFee->others,$gatewayFee->fbaFees,''];
          }
        }
        ksort($feedbackReport);
        foreach($feedbackReport as $so_no => $so)
        {
            $cellData[] = [
                          $so->merchant_id,
                          $so->so_no,
                          $so->txn_id,
                          $so->flex_batch_id,
                          $so->gateway_id,
                          $so->txn_time,
                          $so->currency_id,
                          $so->amount,
                          $so->commissionFees==0?'':$so->commissionFees,
                          $so->receiptFees==0?'':$so->receiptFees,
                          $so->refundOthers==0?'':$so->refundOthers,
                          $so->others,
                          $so->fbaFees,
                          $so->subTotal
                        ];
        }

        Excel::create('flex_batch_'.implode('_', $batchIdList),function($excel) use ($cellData){
            $excel->sheet('feedback', function($sheet) use ($cellData){
              $sheet->rows($cellData);
              $sheet->freezeFirstRow();
            });
        })->download("csv"); //->store('xls',$flexReportPath.'feedback_report/report');

   }

   public function generatePriceministerFeedbackReport(Request $request)
   {
        $batchIdList = $request->batchIdList;
        $pmgw = $request->pmgw;

        $feedbackReport = array();
        $cellData[] = ["Merchant ID","SO Number","PriceMinister ID","Flex Batch ID","Gateway ID","Txn Time","Currency ID","Amount","Commission Fees", "Receipt fees","Refund Others","Others","FBA fees","Subtotal"];
        //flex ria
        $flexRia = new FlexRia();
        $flexRiaList = $flexRia->soFee($batchIdList);
        $soList = [];
        if($flexRiaList->count())
        {
          foreach($flexRiaList as $flex)
          {
            $soList[$flex->so_no][] = $flex;
          }
        }
        if(count($soList) > 0)
        {
          foreach ($soList as $so_no => $so)
          {
              $receiptFees = 0;
              foreach ($so as $fee)
              {
                  if(in_array($fee->fee_status,["P_SO_FEE"]))
                  {
                    $receiptFees += $fee->fee_amount;
                  }
              }
              $fee->receiptFees = $receiptFees;
              $fee->subTotal = $fee->amount + $receiptFees;

              $feedbackReport[$fee->so_no] = $fee;
          }
        }

        //flex refund
        $flexRefund = new FlexRefund;
        $flexRefundList = $flexRefund->soFee($batchIdList);
        $refundList = [];
        if($flexRefundList->count() )
        {
          foreach ($flexRefundList as $flex) {
              $refundList[$flex->so_no][] = $flex;
          }
        }
        if(count($refundList)>0)
        {
          foreach ($refundList as $so_no =>$so)
          {
              $refundOthers = 0;
              foreach ($so as $fee)
              {
                  if(in_array($fee->fee_status,['P_RF']))
                  {
                    $refundOthers += $fee->fee_amount;
                  }
              }
              $refund = $fee;
              $subTotal = $refund->amount + $refundOthers;

              $refund->refundOthers = $refundOthers;
              $refund->subTotal = $subTotal;

              if(array_key_exists($refund->so_no, $feedbackReport))
              {
                  $feedbackReport["R".$refund->so_no] = $refund;//so have receipt order ,also have refund order. should show two lines
              }
              else
              {
                  $feedbackReport[$refund->so_no] = $refund;
              }
          }
        }

        foreach($feedbackReport as $so_no => $so)
        {
            $cellData[] = [
                          $so->merchant_id,
                          $so->so_no,
                          $so->txn_id,
                          $so->flex_batch_id,
                          $so->gateway_id,
                          $so->txn_time,
                          $so->currency_id,
                          $so->amount,
                          '',
                          $so->receiptFees==0?'':$so->receiptFees,
                          $so->refundOthers==0?'':$so->refundOthers,
                          '',
                          '',
                          $so->subTotal
                        ];
        }

        Excel::create('flex_batch_'.implode('_', $batchIdList),function($excel) use ($cellData){
            $excel->sheet('feedback', function($sheet) use ($cellData){
              $sheet->rows($cellData);
              $sheet->freezeFirstRow();
            });
        })->download("csv"); //->store('xls',$flexReportPath.'feedback_report/report');
   }


   public function generateLazadaFeedbackReport(Request $request)
   {
        $batchIdList = $request->batchIdList;
        $pmgw = $request->pmgw;

        $feedbackReport = array();
        $cellData[] = ["Merchant ID","SO Number","Lazada ID","Flex Batch ID","Gateway ID","Txn Time","Currency ID","Amount","Commission Fees", "Receipt fees","Refund Others","Others","FBA fees","Subtotal"];
        //flex ria
        $flexRia = new FlexRia();
        $flexRiaList = $flexRia->soFee($batchIdList);
        $soList = [];
        if($flexRiaList->count())
        {
          foreach($flexRiaList as $flex)
          {
            $soList[$flex->so_no][] = $flex;
          }
        }
        if(count($soList) > 0)
        {
          foreach ($soList as $so_no => $so)
          {
              $commissionFees = 0;
              $receiptFees = 0;
              foreach ($so as $fee)
              {
                  if(in_array($fee->fee_status,["L_COMM"]))
                  {
                    $commissionFees += $fee->fee_amount;
                  }
                  if(in_array($fee->fee_status,["L_PMF"]))
                  {
                    $receiptFees += $fee->fee_amount;
                  }
              }
              $fee->commissionFees = $commissionFees;
              $fee->receiptFees = $receiptFees;
              $fee->subTotal = $fee->amount + $receiptFees + $commissionFees;

              $feedbackReport[$fee->so_no] = $fee;
          }
        }

        //flex refund
        $flexRefund = new FlexRefund;
        $flexRefundList = $flexRefund->soFee($batchIdList);
        $refundList = [];
        if($flexRefundList->count() )
        {
          foreach ($flexRefundList as $flex) {
              $refundList[$flex->so_no][] = $flex;
          }
        }
        if(count($refundList)>0)
        {
          foreach ($refundList as $so_no =>$so)
          {
              $refundOthers = 0;
              foreach ($so as $fee)
              {
                  if(in_array($fee->fee_status,['L_RCOMM']))
                  {
                    $refundOthers += $fee->fee_amount;
                  }
              }
              $refund = $fee;
              $subTotal = $refund->amount + $refundOthers;

              $refund->refundOthers = $refundOthers;
              $refund->subTotal = $subTotal;

              if(array_key_exists($refund->so_no, $feedbackReport))
              {
                  $feedbackReport["R".$refund->so_no] = $refund;//so have receipt order ,also have refund order
              }
              else
              {
                  $feedbackReport[$refund->so_no] = $refund;
              }
          }
        }

        //shipping fee
        $flexSofee = new FlexSoFee();
        $shippingfee = $flexSofee->getLazadaShippingfee($batchIdList);
        foreach ($shippingfee as $fee) {
            $fee->others = $fee->amount;    
            $fee->amount = null;
            if(array_key_exists($fee->so_no, $feedbackReport))
            {
                $feedbackReport[$fee->so_no]->others = $fee->others;
                $feedbackReport[$fee->so_no]->subTotal = $feedbackReport[$fee->so_no]->subTotal + $fee->others;
            }
            else
            {
                $feedbackReport[$fee->so_no] = $fee; 
            }           
        }


        foreach($feedbackReport as $so_no => $so)
        {
            $cellData[] = [
                          $so->merchant_id,
                          $so->so_no,
                          $so->txn_id,
                          $so->flex_batch_id,
                          $so->gateway_id,
                          $so->txn_time,
                          $so->currency_id,
                          $so->amount,
                          $so->commissionFees==0?'':$so->commissionFees,
                          $so->receiptFees==0?'':$so->receiptFees,
                          $so->refundOthers==0?'':$so->refundOthers,
                          $so->others==0?'':$so->others,
                          '',
                          $so->subTotal
                        ];
        }

        Excel::create('flex_batch_'.implode('_', $batchIdList),function($excel) use ($cellData){
            $excel->sheet('feedback', function($sheet) use ($cellData){
              $sheet->rows($cellData);
              $sheet->freezeFirstRow();
            });
        })->download("csv"); //->store('xls',$flexReportPath.'feedback_report/report');
   }

}