<?php

namespace App\Services;

use App\Models\Config;
use App\Models\FlexBatch;
use App\Models\InterfaceFlexRia;
use App\Models\InterfaceFlexGatewayFee;
use App\Models\InterfaceFlexSoFee;
use App\Models\InterfaceFlexRefund;
use App\Models\FlexRia;
use App\Models\FlexSoFee;
use App\Models\FlexGatewayFee;
use App\Models\FlexRefund;
use App\Models\So;
use Excel;

abstract class PaymentGatewayReportService
{
    const WRONG_TRANSACTION_ID = "Wrong transaction id / so_no";

    public $pmgw;
    public $folderPath;

    abstract public function getPmgw();
    abstract public function setPmgw($value);
    abstract public function isRiaRecord($cell);
    /***********************************
    *   If Refund, return 'R'
    *   If Chargeback return 'CB'
    *   return False otherwises
    ***********************************/
    abstract public function isRefundRecord($cell);
    abstract public function isSoFeeRecord($cell);
    abstract public function isRollingReserveRecord($cell);
    abstract public function isGatewayFeeRecord($cell);
    abstract public function isRiaIncludeSoFee();
    abstract public function isRefundIncludeSoFee();

    /***********************************
    *   If Exchange Fee, return 'FX'
    *   If Payment Sent return 'PS'
    *   return False otherwises
    ***********************************/
    abstract protected function insertInterfaceFlexRia($batchId, $status, $cell);
    abstract protected function insertInterfaceFlexSoFee($batchId, $status, $cell);
    abstract protected function insertInterfaceFlexRefund($batchId, $status, $cell);
    abstract protected function insertInterfaceFlexRollingReserve($batchId, $status, $cell);
    abstract protected function insertInterfaceFlexGatewayFee($batchId, $status, $cell);
    abstract protected function afterInsertAllInterface($batchId);

    /************************************
    *   For Paypay, The refund record need to
    *   1. create the interface_flex_refund
    *   2. create the interface_flex_so_fee
    *************************************/
    abstract function insertSoFeeFromRefundRecord($batchId, $status, $cell);
    abstract function insertSoFeeFromRiaRecord($batchId, $status, $cell);
    abstract function insertSoFeeFromRollingReserveRecord($batchId, $status, $cell);

    public function processReport($filename, $email)
    {
        $pmgw = $this->getPmgw();

        $batchId = $this->insertBatch($filename);
        $output = $this->getFileData($filename);

        $batchResult = TRUE;
        $countOutput = count($output);
        if($countOutput > 0)
        {
            foreach($output as $cell)
            {
                $this->insertInterface($batchId, $cell);
            }

            $this->afterInsertAllInterface($batchId);

            $batchResult = $this->insertMaster($batchId);

            if($batchResult)
            {
                FlexBatch::where("id",$batchId)->update(["status"=>"C"]);
            }
            else
            {
                FlexBatch::where("id",$batchId)->update(["status"=>"CE"]);
                $this->sendInvestigateReport($pmgw, $filename, $batchId);
            }
        }
        else
        {
            FlexBatch::where("id",$batchId)->update(["status"=>"F"]);
            $this->sendInvestigateReport($pmgw, $filename, $batchId);
        }
        $this->moveCompleteFile($filename);

        $this->validFlexRiaAmount($pmgw, $filename, $batchId , $email);

        return array($batchResult, $batchId);
    }

    public function moveCompleteFile($filename)
    {
        if (copy($this->getFolderPath().$filename, $this->getFolderPath()."complete/".$filename))
        {
            unlink($this->getFolderPath().$filename);
        }
    }

    public function validFlexRiaAmount($pmgw, $filename, $batchId, $email){
        $isSend = false;
        $message = "Payment Gateway: " . $pmgw . "\r\n";
        $message .= "File Name: " . $filename . "\r\n";
        $message .= "Batch ID: " . $batchId . "\r\n\r\n";

        $flexRiaList = FlexRia::where("flex_batch_id",$batchId)->with("so")->get();

        if($flexRiaList->count())
        {
            $message .= "RIA:\r\n";
            $message .= "ORDER NO,TRANSACTION ID,ORDER AMT,REPORT AMT\r\n";
            foreach($flexRiaList as $ria)
            {
                if($ria->so->amount != $ria->amount ){
                    $message .= $ria->so_no. "," . $ria->txn_id . "," . $ria->so->amount.",".$ria->amount. "\r\n";
                    $isSend = true;
                }
            }
            $message .= "\r\n\r\n";
        }

        if($isSend)
        {
            @mail($email, "[ESG] Report Amount is not same as Order amount", $message);
        }

    }

    public function insertBatch($filename)
    {
        set_time_limit(3000);
        if(is_file($this->getFolderPath().$filename))
        {
            //echo $filename;
            $batchObj = FlexBatch::where("filename",$filename)->first();
            if(!$batchObj)
            {
                return FlexBatch::insertGetId([
                    "gateway_id"=>$this->getPmgw(),
                    "filename"=>$filename
                    ]);
            }
            else
            {
                echo "file already in batch";exit;
                //file already in batch
            }
        }
        else
        {
            echo "file does not exists:<br />" . $this->getFolderPath().$filename;exit;
            //invalid file path
        }
    }

    protected function insertInterface($batchId, $cell)
    {
        if ($this->isRiaRecord($cell))
        {
            $this->insertInterfaceFlexRia($batchId, 'RIA', $cell);
        }
        else if ($refundStatus = $this->isRefundRecord($cell))
        {
            $this->insertInterfaceFlexRefund($batchId, $refundStatus, $cell);
        }
        else if ($feeStatus = $this->isSoFeeRecord($cell))
        {
            $this->insertInterfaceFlexSoFee($batchId, $feeStatus, $cell);
        }
        else if ($rollingStatus = $this->isRollingReserveRecord($cell))
        {
            //$this->insertInterfaceFlexRollingReserve($batchId, $rollingStatus, $cell);
        }
        else if ($gatewayFeeStatus = $this->isGatewayFeeRecord($cell))
        {
            $this->insertInterfaceFlexGatewayFee($batchId, $gatewayFeeStatus, $cell);
        }
    }

    public function getFolderPath()
    {
        if(!$this->folderPath)
        {
            $this->folderPath = Config::find("flex_pmgw_report_loaction")->value.$this->getPmgw()."/";
        }
        return $this->folderPath;
    }

    public function getFileData($filename, $delimiter = ",")
    {
        $filePath = $this->getFolderPath().$filename;
        return Excel::load($filePath)->get();
    }

    protected function createInterfaceFlexRia($batchId, $status, $cell, $includeFsf = true)
    {

        $ifrObj = [];

        if(!$cell->so_no && $cell->txn_id)
        {
            if($so = So::where("txn_id",$cell->txn_id)->select("so_no")->first())
            {
                $cell->so_no = $so->so_no;
            }
        }

        $ifrObj = [
                "so_no"=>$cell->so_no,
                "flex_batch_id"=>$batchId,
                "gateway_id"=> $this->getPmgw(),
                "txn_id"=>$cell->txn_id,
                "txn_time"=>$cell->txn_time,
                "currency_id"=>$cell->currency_id,
                "amount"=>$cell->amount,
                "status"=>$status,
                "batch_status"=>"N"
            ];


        if(!$ifrObj["so_no"])
        {
            $ifrObj["so_no"] = " ";
            $ifrObj["batch_status"] = "F";
            $ifrObj["failed_reason"] = PaymentGatewayReportService::WRONG_TRANSACTION_ID;
        }

        if( $res = InterfaceFlexRia::insert($ifrObj) && $ifrObj["batch_status"] != "F" )
        {
            if ($includeFsf)
            {
                //$this->insert_so_fee_from_ria_record($batchId, $status, $dto_obj);
            }
        }
        return $ifrObj;
    }

    protected function createInterfaceFlexGatewayFee($batchId, $status, $cell)
    {
        $interfaceFlexGatewayFee = [
            "flex_batch_id"=>$batchId,
            "gateway_id"=>$this->getPmgw(),
            "txn_id"=>$cell->txn_id,
            "txn_time"=>$cell->txn_time,
            "currency_id"=>$cell->currency_id,
            "amount"=>$cell->amount,
            "status"=>$status,
            "batch_status"=>"N"
            ];

        $result = InterfaceFlexGatewayFee::insert($interfaceFlexGatewayFee);
        return $result;
    }

    protected function createInterfaceFlexSoFee($batchId, $status, $cell)
    {
        $interfaceFlexSoFee = [
            "so_no"=>$cell->so_no,
            "flex_batch_id"=>$batchId,
            "gateway_id"=>$this->getPmgw(),
            "txn_id"=>$cell->txn_id,
            "txn_time"=>$cell->txn_time,
            "currency_id"=>$cell->currency_id,
            "amount"=>$cell->amount,
            "status"=>$status,
            "batch_status"=>"N"
            ];

        if(!$interfaceFlexSoFee["so_no"])
        {
            $interfaceFlexSoFee["so_no"] = " ";
            $interfaceFlexSoFee["batch_status"] = "F";
            $interfaceFlexSoFee["failed_reason"] = PaymentGatewayReportService::WRONG_TRANSACTION_ID;
        }

        $result = InterfaceFlexSoFee::insert($interfaceFlexSoFee);
    }

    protected function createInterfaceFlexRefund($batchId, $status, $cell, $includeFsf = false)
    {
        $interfaceFlexRefundObj = [];
        if(!$cell->so_no && $cell->txn_id)
        {
            if($so = So::where("txn_id",$cell->txn_id)->where("platform_group_order","1")->select("so_no")->first())
            {
                $cell->so_no = $so->so_no;
            }
        }
        $interfaceFlexRefundObj = [
                "so_no"=>$cell->so_no,
                "flex_batch_id"=>$batchId,
                "gateway_id"=> $this->getPmgw(),
                'internal_txn_id'=>$cell->internal_txn_id,
                "txn_id"=>$cell->txn_id,
                "txn_time"=>$cell->txn_time,
                "currency_id"=>$cell->currency_id,
                "amount"=>$cell->amount,
                "status"=>$status,
                "batch_status"=>"N"
                ];
        if(!$interfaceFlexRefundObj["so_no"])
        {
            $interfaceFlexRefundObj["so_no"] = " ";
            $interfaceFlexRefundObj["batch_status"] = "F";
            $interfaceFlexRefundObj["failed_reason"] = PaymentGatewayReportService::WRONG_TRANSACTION_ID;
        }

        if( $res = InterfaceFlexRefund::insert($interfaceFlexRefundObj) && $interfaceFlexRefundObj["batch_status"] != "F" )
        {
            if ($includeFsf)
            {
                //$this->insert_so_fee_from_refund_record($batch_id, $status, $dto_obj);
            }
        }
    }

    public function insertMaster($batchId)
    {
        $returnResult = TRUE;

        if($this->insertFlexRia($batchId) === FALSE)
        {
            $returnResult = FALSE;
        }

        if($this->insertFlexSoFee($batchId) === FALSE)
        {
            $returnResult = FALSE;
        }

        if($this->insertFlexRefund($batchId) === FALSE)
        {
            $returnResult = FALSE;
        }

        if($this->insertFlexRollingReserve($batchId) === FALSE)
        {
            $returnResult = FALSE;
        }

        if($this->insertFlexGatewayFee($batchId) === FALSE)
        {
            $returnResult = FALSE;
        }

        return $returnResult;
    }

    public function insertFlexRia($batchId)
    {
        $interfaceFlexRia = new InterfaceFlexRia();
        $interfaceFlexRiaList = $interfaceFlexRia->getFlexRiaByBatch($batchId);

        $flexRia = new FlexRia();

        if($interfaceFlexRiaList->count())
        {
            $returnResult = TRUE;

            foreach($interfaceFlexRiaList AS $ria)
            {
                if($ria->batch_status == 'N')
                {
                    $flexRiaSingle = $flexRia->where("so_no",$ria->so_no)->where("gateway_id",$ria->gateway_id)->where("status",$ria->status)->where("txn_time",$ria->txn_time)->first();

                    if($flexRiaSingle)
                    {
                        if( $flexRiaSingle->amount == $ria->amount )
                        {
                            $this->_updateInterfaceFlexRiaStatusByGroup($batchId, $ria->so_no, $ria->status, "C", "duplicated record");
                        }
                        else
                        {

                            $flexRiaSingle->flex_batch_id = $ria->flex_batch_id;
                            $flexRiaSingle->amount = $ria->amount;
                            if($flexRia->where("so_no",$flexRiaSingle->so_no)->where("txn_time",$flexRiaSingle->txn_time)->update($flexRiaSingle->toArray()))
                            {
                                $this->_updateInterfaceFlexRiaStatusByGroup($batchId, $ria->so_no, $ria->status, "I", "record updated on ". date("Y-m-d H:i:s"));
                                $returnResult = FALSE;
                            }
                            else
                            {
                                $this->_updateInterfaceFlexRiaStatusByGroup($batchId, $ria->so_no, $ria->status, "F", "update record error");
                                $returnResult = FALSE;
                            }
                        }
                    }
                    else
                    {
                        $insert = ['so_no'=>$ria->so_no,
                                  'flex_batch_id'=>$ria->flex_batch_id,
                                  'gateway_id'=>$ria->gateway_id,
                                  'txn_id'=>$ria->txn_id,
                                  'txn_time'=>$ria->txn_time,
                                  'currency_id'=>$ria->currency_id,
                                  'amount'=>$ria->amount,
                                  'net_usd_amt'=>$ria->net_usd_amt,
                                  'status'=>$ria->status];

                        if($this->validTxnId($insert))
                        {
                            if($flexRia->insert($insert))
                            {
                                $this->_updateInterfaceFlexRiaStatusByGroup($batchId, $ria->so_no, $ria->status, "S", "");
                            }
                            else
                            {
                                if(!$failedReason = $this->validSoNo($insert))
                                {
                                    $failedReason = "datebase error";
                                }

                                $this->_updateInterfaceFlexRiaStatusByGroup($batchId, $ria->so_no, $ria->status, "F", $failedReason);
                                $returnResult = FALSE;
                            }
                        }
                        else
                        {
                            $this->_updateInterfaceFlexRiaStatusByGroup($batchId, $ria->so_no, $ria->status, "F", "invalid txn_id");
                            $returnResult = FALSE;
                        }
                    }
                }
                elseif($ria->batch_status == 'F')
                {
                    $returnResult = FALSE;
                }
            }
            return $returnResult;
        }
        return TRUE;
    }

    public function insertFlexRefund($batchId)
    {
        $interfaceFlexRefund = new InterfaceFlexRefund();
        $interfaceFlexRefundList = $interfaceFlexRefund->getFlexRefundByBatch($batchId);

        $flexRefund = new FlexRefund();

        if($interfaceFlexRefundList->count())
        {
            $returnResult = TRUE;
            foreach($interfaceFlexRefundList AS $refund)
            {
                if($refund->batch_status == 'N')
                {
                    $flexRefundSingle = $flexRefund->where(["so_no"=>$refund->so_no,"gateway_id"=>$refund->gateway_id,"status"=>$refund->status,"txn_time"=>$refund->txn_time,"internal_txn_id"=>$refund->internal_txn_id])->first();

                    if($flexRefundSingle)
                    {
                        if( $flexRefundSingle->amount == $refund->amount )
                        {
                            $this->_updateInterfaceFlexRefundStatusByGroup($batchId, $refund->so_no, $refund->status, "C", "duplicated record");
                        }
                        else
                        {
                            $flexRefundSingle->flex_batch_id = $refund->flex_batch_id;
                            $flexRefundSingle->amount = $refund->amount;

                            if($flexRefund->where(["so_no"=>$flexRefundSingle->so_no,"gateway_id"=>$flexRefundSingle->gateway_id,"status"=>$flexRefundSingle->status,"txn_time"=>$flexRefundSingle->txn_time,"internal_txn_id"=>$flexRefundSingle->internal_txn_id])->update($flexRefundSingle->toArray()))
                            {
                                $this->_updateInterfaceFlexRefundStatusByGroup($batchId, $refund->so_no, $refund->status, "I", "record updated on ". date("Y-m-d H:i:s"));
                                $returnResult = FALSE;
                            }
                            else
                            {
                                $this->_updateInterfaceFlexRefundStatusByGroup($batchId, $refund->so_no, $refund->status, "F", "update record error");
                                $returnResult = FALSE;
                            }
                        }
                    }
                    else
                    {
                        $insert = ['so_no'=>$refund->so_no,
                                  'flex_batch_id'=>$refund->flex_batch_id,
                                  'gateway_id'=>$refund->gateway_id,
                                  'internal_txn_id'=>$refund->internal_txn_id,
                                  'txn_id'=>$refund->txn_id,
                                  'txn_time'=>$refund->txn_time,
                                  'currency_id'=>$refund->currency_id,
                                  'amount'=>$refund->amount,
                                  'status'=>$refund->status];

                        if($this->validTxnId($insert))
                        {
                            if($flexRefund->insert($insert))
                            {
                                $this->_updateInterfaceFlexRefundStatusByGroup($batchId, $refund->so_no, $refund->status, "S", "");
                            }
                            else
                            {
                                if(!$failedReason = $this->validSoNo($insert))
                                {
                                    $failedReason = "datebase error";
                                }
                                $this->_updateInterfaceFlexRefundStatusByGroup($batchId, $refund->so_no, $refund->status, "F", $failedReason);
                                $returnResult = FALSE;
                            }
                        }
                        else
                        {
                            $this->_updateInterfaceFlexRefundStatusByGroup($batchId, $refund->so_no, $refund->status, "F", "invalid txn_id");
                            $returnResult = FALSE;
                        }
                    }
                }
                elseif($refund->batch_status == 'F')
                {
                    $returnResult = FALSE;
                }
            }

            return $returnResult;
        }

        return TRUE;
    }

    public function insertFlexRollingReserve()
    {
        return TRUE;
    }

    private function _updateInterfaceFlexRiaStatusByGroup($batchId, $soNo, $status, $batchStatus, $failedReason)
    {

        $interfaceFlexRia =new InterfaceFlexRia();
        $collect = $interfaceFlexRia->where("flex_batch_id",$batchId)
                        ->where("gateway_id",$this->getPmgw())
                        ->where("so_no",$soNo)
                        ->where("status",$status)
                        ->get();

        if($collect->count())
        {
            foreach($collect as $ria)
            {
                $ria->batch_status = $batchStatus;
                if ($failedReason) $ria->failed_reason = $failedReason;
                $ria->save();
            }
        }
    }
    private function _updateInterfaceFlexRefundStatusByGroup($batchId, $soNo, $status, $batchStatus, $failedReason)
    {
        $interfaceFlexRefund = new InterfaceFlexRefund();
        $collect = $interfaceFlexRefund->where("flex_batch_id",$batchId)
                        ->where("gateway_id",$this->getPmgw())
                        ->where("so_no",$soNo)
                        ->where("status",$status)
                        ->get();

        if($collect->count())
        {
            foreach($collect as $refund)
            {
                $refund->batch_status = $batchStatus;
                if ($failedReason) $refund->failed_reason = $failedReason;
                $refund->save();
            }
        }
    }
    public function updateInterfaceSoFeeStatusByGroup($batchId, $soNo, $status, $batchStatus, $failedReason)
    {
        $interfaceFlexSoFee = new InterfaceFlexSoFee();

        $collect = $interfaceFlexSoFee->where(["flex_batch_id" => $batchId,
                                                "gateway_id" => $this->getPmgw(),
                                                "so_no" => $soNo,
                                                "status" => $status])->get();
        if($collect->count()){
            foreach($collect as $sofee)
            {
                $sofee->batch_status = $batchStatus;
                if ($failedReason)  $sofee->failed_reason = $failedReason;
                $sofee->save();
            }
        }
    }
    public function validTxnId($interfaceRia)
    {
        return true;
    }

    public function validSoNo($interfaceRia)
    {
        return false;
    }


    public function insertFlexSoFee($batchId)
    {
        $interfaceFlexSoFee = new InterfaceFlexSoFee();
        $interfaceFlexSoFeeList =$interfaceFlexSoFee->getSoFeeByBatch($batchId);

        if($interfaceFlexSoFeeList->count())
        {
            $returnResult = TRUE;
            $flexSoFee = new FLexSoFee();

            foreach($interfaceFlexSoFeeList AS $soFee)
            {
                if($soFee->batch_status == 'N')
                {
                    $soFeeSingle = $flexSoFee->where(["so_no"=>$soFee->so_no, "gateway_id"=>$soFee->gateway_id, "status"=>$soFee->status, "txn_id"=>$soFee->txn_id, "txn_time"=>$soFee->txn_time])->first();
                    if ($soFeeSingle)
                    {

                        if( $soFeeSingle->amount == $soFee->amount )
                        {
                            $this->updateInterfaceSoFeeStatusByGroup($batchId, $soFee->so_no, $soFee->status, "C", "duplicated record");
                        }
                        else
                        {
                            $soFeeSingle->flex_batch_id = $soFee->flex_batch_id;
                            $soFeeSingle->amount = $soFee->amount;

                            if($flexSoFee->where(["so_no"=>$soFeeSingle->so_no,"txn_time"=>$soFeeSingle->txn_time])->update($soFeeSingle->toArray()))
                            {
                                $this->updateInterfaceSoFeeStatusByGroup($batchId, $soFee->so_no, $soFee->status, "I", "record updated on ". date("Y-m-d H:i:s"));
                            }
                            else
                            {
                                $this->updateInterfaceSoFeeStatusByGroup($batchId, $soFee->so_no, $soFee->status, "F", "update record error");
                                $returnResult = FALSE;
                            }
                        }
                    }
                    else
                    {
                        $insert = ['so_no'=>$soFee->so_no,
                                  'flex_batch_id'=>$soFee->flex_batch_id,
                                  'gateway_id'=>$soFee->gateway_id,
                                  'txn_id'=>$soFee->txn_id,
                                  'txn_time'=>$soFee->txn_time,
                                  'currency_id'=>$soFee->currency_id,
                                  'amount'=>$soFee->amount,
                                  'status'=>$soFee->status];

                        if($this->validTxnId($insert))
                        {
                            if($flexSoFee->insert($insert))
                            {
                                $this->updateInterfaceSoFeeStatusByGroup($batchId, $soFee->so_no, $soFee->status, "S", "");
                            }
                            else
                            {
                                if(!$failedReason = $this->validSoNo($insert))
                                {
                                    $failedReason = "datebase errorMessage";
                                }
                                $this->updateInterfaceSoFeeStatusByGroup($batchId, $soFee->so_no, $soFee->status, "F", $failedReason);
                                $returnResult = FALSE;
                            }
                        }
                        else
                        {
                            $this->updateInterfaceSoFeeStatusByGroup($batchId, $soFee->so_no, $soFee->status, "F", "invalid txn_id");
                            $returnResult = FALSE;
                        }
                    }
                }
                elseif($soFee->batch_status == 'F')
                {
                    $returnResult = FALSE;
                }
            }

            return $returnResult;
        }

        return TRUE;
    }

    public function insertFlexGatewayFee($batchId)
    {
        $interfaceFlexGatewayFee = new InterfaceFlexGatewayFee();
        $interfaceFlexGatewayFeeList = $interfaceFlexGatewayFee->where("flex_batch_id",$batchId)->get();

        if($interfaceFlexGatewayFeeList->count())
        {
            $returnResult = TRUE;
            $flexGatewayFee = new FlexGatewayFee();

            foreach($interfaceFlexGatewayFeeList AS $gatewayFee)
            {
                if($gatewayFee->batch_status == 'N')
                {
                    $gatewayFeeSingle = $flexGatewayFee->where(["gateway_id"=>$gatewayFee->gateway_id, "status"=>$gatewayFee->status, "txn_id"=>$gatewayFee->txn_id, "txn_time"=>$gatewayFee->txn_time])->first();

                    if($gatewayFeeSingle)
                    {
                        if( $gatewayFeeSingle->amount == $gatewayFee->amount )
                        {
                            $gatewayFee->batch_status = "C";
                            $gatewayFee->failed_reason = "duplicated record";
                            $gatewayFee->save();
                        }
                        else
                        {
                            $gatewayFeeSingle->flex_batch_id = $gatewayFee->flex_batch_id;
                            $gatewayFeeSingle->amount = $gatewayFee->amount;

                            if($flexGatewayFee->where(["gateway_id"=>$gatewayFee->gateway_id, "status"=>$gatewayFee->status, "txn_id"=>$gatewayFee->txn_id, "txn_time"=>$gatewayFee->txn_time])->update($gatewayFeeSingle->toArray()))
                            {
                                $gatewayFee->batch_status = "I";
                                $gatewayFee->failed_reason = "record updated on ". date("Y-m-d H:i:s");
                                $gatewayFee->save();
                            }
                            else
                            {
                                $gatewayFee->batch_status = "F";
                                $gatewayFee->failed_reason = "update record error";
                                $gatewayFee->save();
                                $returnResult = FALSE;
                            }
                        }
                    }
                    else
                    {
                         $insert = ['flex_batch_id'=>$gatewayFee->flex_batch_id,
                                  'gateway_id'=>$gatewayFee->gateway_id,
                                  'txn_id'=>$gatewayFee->txn_id,
                                  'txn_time'=>$gatewayFee->txn_time,
                                  'currency_id'=>$gatewayFee->currency_id,
                                  'amount'=>$gatewayFee->amount,
                                  'status'=>$gatewayFee->status];

                        if($flexGatewayFee->insert($insert))
                        {
                            //gatewayfee have not Multiple records, so only save one
                            $gatewayFee->batch_status = "S";
                            $gatewayFee->save();
                        }
                        else
                        {
                            $gatewayFee->failed_reason = "insert gatewayFee error";
                            $gatewayFee->batch_status = "F";
                            $gatewayFee->save();
                            $returnResult = FALSE;
                        }
                    }
                }
                elseif($gatewayFee->batch_status == 'F')
                {
                    $returnResult = FALSE;
                }
            }

            return $returnResult;
        }
        return TRUE;
    }

    public function sendInvestigateReport($pmgw, $filename, $batchId)
    {
        $totalErr = 0;

        $message = "Payment Gateway: " . $pmgw . "\r\n";
        $message .= "File Name: " . $filename . "\r\n";
        $message .= "Batch ID: " . $batchId . "\r\n\r\n";

        $interfaceFlexRiaList = InterfaceFlexRia::where("flex_batch_id",$batchId)->whereIn("batch_status",['F', 'I'])->get();
        if($interfaceFlexRiaList->count())
        {
            $totalErr += $interfaceFlexRiaList->count();
            $message .= "RIA:\r\n";
            $message .= "txn_id,so_no,failed_reason\r\n";
            foreach($interfaceFlexRiaList as $flex)
            {
                $message .= $flex->txn_id . "," . $flex->so_no . "," . $flex->failed_reason . "\r\n";
            }
            $message .= "\r\n\r\n";
        }

        // $interfaceFlexGatewayFeeList
        // if($ifrf_list = $this->get_ifrf_dao()->get_list(array("flex_batch_id"=>$batch_id, "batch_status IN ('F', 'I')"=>null), array("limit"=>-1)))
        // {
        //     if(count((array)$ifrf_list) > 0)
        //     {
        //         $totalErr += count((array)$ifrf_list);

        //         $message .= "Refund:\r\n";
        //         $message .= "txn_id,so_no,failed_reason\r\n";

        //         foreach($ifrf_list as $ifrf_obj)
        //         {
        //             $message .= $ifrf_obj->get_txn_id() . "," . $ifrf_obj->get_so_no() . "," . $ifrf_obj->get_failed_reason() . "\r\n";
        //         }
        //         $message .= "\r\n\r\n";
        //     }
        // }

        $interfaceFlexSoFeeList = InterfaceFlexSoFee::where("flex_batch_id",$batchId)->whereIn("batch_status",['F', 'I'])->get();
        if($interfaceFlexSoFeeList->count())
        {
            $totalErr += $interfaceFlexSoFeeList->count();
            $message .= "So Fee:\r\n";
            $message .= "txn_id,so_no,failed_reason\r\n";
            foreach($interfaceFlexSoFeeList as $flex)
            {
                $message .= $flex->txn_id . "," . $flex->so_no . "," . $flex->failed_reason . "\r\n";
            }
            $message .= "\r\n\r\n";
        }

        // if($ifrr_list = $this->get_ifrr_dao()->get_list(array("flex_batch_id"=>$batch_id, "batch_status IN ('F', 'I')"=>null), array("limit"=>-1)))
        // {
        //     if(count((array)$ifrr_list) > 0)
        //     {
        //         $totalErr += count((array)$ifrr_list);

        //         $message .= "Rolling Reserve:\r\n";
        //         $message .= "txn_id,so_no,failed_reason\r\n";

        //         foreach($ifrr_list as $ifrr_obj)
        //         {
        //             $message .= $ifrr_obj->get_txn_id() . "," . $ifrr_obj->get_so_no() . "," . $ifrr_obj->get_failed_reason() . "\r\n";
        //         }
        //         $message .= "\r\n\r\n";
        //     }
        // }

        $interfaceFlexGatewayFeeList = InterfaceFlexGatewayFee::where("flex_batch_id",$batchId)->whereIn("batch_status",['F', 'I'])->get();
        if($interfaceFlexGatewayFeeList->count())
        {
                $totalErr += $interfaceFlexGatewayFeeList->count();
                $message .= "Gateway Fee:\r\n";
                $message .= "txn_id,failed_reason\r\n";
                foreach($interfaceFlexGatewayFeeList as $flex)
                {
                    $message .= $flex->txn_id . "," . $flex->failed_reason . "\r\n";
                }
                $message .= "\r\n\r\n";
        }

        if($totalErr > 0)
        {
            //mail("flexadmin@eservicesgroup.com", "[ESG] Gateway Report Error", $message);
            @mail("milo.chen@eservicesgroup.com", "[ESG] Gateway Report Error", $message);
        }
    }


}
