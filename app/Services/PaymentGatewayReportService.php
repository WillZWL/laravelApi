<?php

namespace App\Services;

use App\Models\Config;
use App\Models\FlexBatch;
use Excel;

abstract class PaymentGatewayReportService
{
    const WRONG_TRANSACTION_ID = "Wrong transaction id / so_no";

    private $pmgw;
    private $folderPath;

    abstract public function getPmgw();
    abstract public function setPmgw($value);
    abstract public function isRiaRecord($dtoObj);
    /***********************************
    *   If Refund, return 'R'
    *   If Chargeback return 'CB'
    *   return False otherwises
    ***********************************/
    abstract public function isRefundRecord($dtoObj);
    abstract public function isSoFeeRecord($dtoObj);
    abstract public function isRollingReserveRecord($dtoObj);
    abstract public function isGatewayFeeRecord($dtoObj);
    abstract public function isRiaIncludeSoFee();
    abstract public function isRefundIncludeSoFee();

    /***********************************
    *   If Exchange Fee, return 'FX'
    *   If Payment Sent return 'PS'
    *   return False otherwises
    ***********************************/
    abstract protected function insertInterfaceFlexRia($batch_id, $status, $dtoObj);
    abstract protected function insertInterfaceFlexSoFee($batch_id, $status, $dtoObj);
    abstract protected function insertInterfaceFlexRefund($batch_id, $status, $dtoObj);
    abstract protected function insertInterfaceFlexRollingReserve($batch_id, $status, $dtoObj);
    abstract protected function insertInterfaceFlexGatewayFee($batch_id, $status, $dtoObj);
    abstract protected function afterInsertAllInterface($batch_id);

    /************************************
    *   For Paypay, The refund record need to
    *   1. create the interface_flex_refund
    *   2. create the interface_flex_so_fee
    *************************************/
    abstract function insertSoFeeFromRefundRecord($batch_id, $status, $dtoObj);
    abstract function insertSoFeeFromRiaRecord($batch_id, $status, $dtoObj);
    abstract function insertSoFeeFromRollingReserveRecord($batch_id, $status, $dtoObj);

    public function processReport($filename)
    {
        $pmgw = $this->getPmgw();

        //1、先验证数据能否读取,不能的话返回错误信息file format error
        //2、读取数据后插入interface表
        //3、验证数据后插入master表
        $batchId = $this->insertBatch($filename);
        $output = $this->getFileData($filename);
        
        $batch_result = TRUE;
        $count_output = count($output);
        if($count_output > 0)
        {
            foreach($output as $dtoObj)
            {
                $this->insertInterface($batchId, $dtoObj);
                
            }
dd(1);
            $this->after_insert_all_interface($batch_id);

            $batch_result = $this->insert_master($batch_id);

            if($batch_result)
            {
                $this->complete_batch($batch_id, "C");
            }
            else
            {
                $this->complete_batch($batch_id, "CE");
                $this->send_investigate_report($pmgw, $filename, $batch_id);
            }
        }
        else
        {
            $this->complete_batch($batch_id, "F");
            $this->send_investigate_report($pmgw, $filename, $batch_id);
        }
        $this->move_complete_file($filename);

        if(strpos($pmgw,'linio') !== false) 
        {
            $this->valid_so_amount($pmgw, $filename, $batch_id);
        }

        return array($batch_result, $batch_id);
    }

    public function insertBatch($filename)
    {
        set_time_limit(3000);
        if(is_file($this->getFolderPath().$filename))
        {
            echo $filename;
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
        return Excel::load($filePath, function($reader) {})->get();
    }

    protected function insertInterface($batchId, $dtoObj)
    {
        if ($this->isRiaRecord($dtoObj))
        {
            echo 'isRiaRecord';
            //$this->insertInterfaceFlexRia($batchId, 'RIA', $dtoObj);
        }
        else if ($refundStatus = $this->isRefundRecord($dtoObj))
        {
            $this->insertInterfaceFlexRefund($batchId, $refundStatus, $dtoObj);
        }
        else if ($feeStatus = $this->isSoFeeRecord($dtoObj))
        {
            $this->insertInterfaceFlexSoFee($batchId, $feeStatus, $dtoObj);
        }
        else if ($rollingStatus = $this->isRollingReserveRecord($dtoObj))
        {
            $this->insertInterfaceFlexRollingReserve($batchId, $rollingStatus, $dtoObj);
        }
        else if ($gatewayFeeStatus = $this->isGatewayFeeRecord($dtoObj))
        {
            $this->insertInterfaceFlexGatewayFee($batchId, $gatewayFeeStatus, $dtoObj);
        }
    }
}
