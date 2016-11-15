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
        die;
        // $data['result'] = $feedback_report;
        // $this->write_file($data, 'report', 'feedback_report', false);

        // DEFINE('REPORT_PATH', $this->get_config_srv()->value_of("flex_report_path"));
        // $this->generate_zip_file(REPORT_PATH.'feedback_report/report/', 'feedback_report.zip');

        // return array('filename' => 'feedback_report.zip', 'file_path' => REPORT_PATH.'feedback_report/report/feedback_report.zip');
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

              $feedbackReport[$fee->so_no] = $fee;
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
              $refundList[$flex->so_no][] = $flex;
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
                  if(in_array($fee->fee_status,['A_RF_SC','A_RF_RFOIP','A_RF_RAF','A_RF','A_RO_S','A_RO']))
                  {
                    $refundOthers += $fee->fee_amount;
                  }
              }
              $refund = $fee;
              $subTotal = $refund->amount + $commissionFees + $refundOthers;
              
              $refund->commissionFees = $commissionFees;
              $refund->refundOthers = $refundOthers;
              $refund->subTotal = $subTotal;

              $feedbackReport[$refund->so_no] = $refund;
              //$cellData[] = [$refund->merchant_id,$refund->so_no,$refund->txn_id,$refund->flex_batch_id,$refund->gateway_id,$refund->txn_time,$refund->currency_id,$refund->amount,$refund->commissionFees==0?'':$refund->commissionFees,'',$refund->refundOthers==0?'':$refund->refundOthers,'','',$refund->subTotal];
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
                $so = So::where(["platform_order_id"=>$gatewayFee->txn_id,"platform_group_order"=>1])->first();
                if(array_key_exists($so->so_no, $feedbackReport))
                {
                  $feedbackReport[$so->so_no]->others = $gatewayFee->others;
                  $feedbackReport[$so->so_no]->fbaFees = $gatewayFee->fbaFees;
                  $feedbackReport[$so->so_no]->subTotal = $feedbackReport[$so->so_no]->subTotal + $gatewayFee->others +$gatewayFee->fbaFees;
                }
                else
                {
                  $gatewayFee->txn_id = null;
                  $feedbackReport["gateway".$gatewayNum++] = $gatewayFee;  
                }
              }else{
                $feedbackReport["gateway".$gatewayNum++] = $gatewayFee;
              }
              //$cellData[] = ['','','',$gatewayFee->flex_batch_id,$gatewayFee->gateway_id,$gatewayFee->txn_time,$gatewayFee->currency_id,'','','','',$gatewayFee->others,$gatewayFee->fbaFees,''];
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
                          $so->others,
                          $so->fbaFees,
                          $so->subTotal
                        ];
        }
        
        $flexReportPath = Config::find("flex_report_path")->value;
        Excel::create('flex_batch_'.implode('_', $batchIdList),function($excel) use ($cellData){
            $excel->sheet('feedback', function($sheet) use ($cellData){        
              $sheet->rows($cellData);
              $sheet->freezeFirstRow();
            });
        })->download("csv"); //->store('xls',$flexReportPath.'feedback_report/report');

   }

   public function generatePriceministerFeedbackReport(Request $request)
   {
      return false;
   }

  
}