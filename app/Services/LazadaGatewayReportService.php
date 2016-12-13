<?php

namespace App\Services;

use App\Models\So;

class LazadaGatewayReportService extends PaymentGatewayReportService
{
    public function __construct()
    {
        //$this->setFolderPath();
    }

    public function getPmgw()
    {
        return $this->pmgw;
    }
    public function setPmgw($value){
        $this->pmgw = $value;
    }

    public function isRiaRecord($cell)
    {
        if($cell->transaction_type == "Item Price Credit")
        {
            return true;
        }
        return false;
    }
    /***********************************
    *   If Refund, return 'R'
    *   If Chargeback return 'CB'
    *   return False otherwises
    ***********************************/
    public function isRefundRecord($cell)
    {
        if($cell->transaction_type == "Item Price")
        {
            return "R";
        }
        return false;
    }
    public function isSoFeeRecord($cell)
    {
        if($cell->transaction_type == "Commission")
        {
            return "L_COMM";
        }
        else if($cell->transaction_type == "Shipping Fee (Item Level)")
        {
            return "L_SF";
        }
        else if($cell->transaction_type == "Commission Credit")
        {
            return "L_RCOMM";
        }
        else if($cell->transaction_type == "Payment Fee")
        {
            return "L_PMF";
        }
        return false;
    }
    public function isRollingReserveRecord($cell)
    {
        return false;
    }
    public function isGatewayFeeRecord($cell)
    {
        return false;
    }
    public function isRiaIncludeSoFee()
    {
        return false;
    }
    public function isRefundIncludeSoFee()
    {
        return false;
    }

    /***********************************
    *   If Exchange Fee, return 'FX'
    *   If Payment Sent return 'PS'
    *   return False otherwises
    ***********************************/
    protected function insertInterfaceFlexRia($batchId, $status, $cell)
    {
        $this->_data_reform($cell);
        $this->createInterfaceFlexRia($batchId, $status, $cell,FALSE);
    }
    protected function insertInterfaceFlexSoFee($batchId, $status, $cell)
    {
        $this->_data_reform($cell);
        $this->createInterfaceFlexSoFee($batchId, $status, $cell);
    }
    protected function insertInterfaceFlexRefund($batchId, $status, $cell)
    {
        $this->_data_reform($cell);
        $cell->internal_txn_id = $cell->txn_id;
        $this->createInterfaceFlexRefund($batchId, $status, $cell);
    }
    protected function insertInterfaceFlexRollingReserve($batchId, $status, $cell)
    {

    }
    protected function insertInterfaceFlexGatewayFee($batchId, $status, $cell)
    {
        $this->_data_reform($cell);
        return $this->createInterfaceFlexGatewayFee($batchId, $status, $cell);
    }
    protected function afterInsertAllInterface($batchId)
    {
        return true;
    }

    /************************************
    *   For Paypay, The refund record need to
    *   1. create the interface_flex_refund
    *   2. create the interface_flex_so_fee
    *************************************/
    function insertSoFeeFromRefundRecord($batchId, $status, $cell)
    {

    }
    function insertSoFeeFromRiaRecord($batchId, $status, $cell)
    {

    }
    function insertSoFeeFromRollingReserveRecord($batchId, $status, $cell)
    {

    }

    private function _data_reform($cell)
    {
        $settlementDate = date("Y-m-d", strtotime("+10 days",strtotime(explode(" - ", $cell->statement)[1])));

        $so = So::where("platform_order_id",$cell->order_no)->where("platform_group_order","1")->select('so_no','currency_id')->first();

        if($so)
        {
            So::where("so_no",$so->so_no)->update(["settlement_date"=>$settlementDate]);
            $cell->so_no = $so->so_no;
            $cell->currency_id = $so->currency_id;
        }
        $cell->txn_time = date("Y-m-d",strtotime($cell->transaction_date));
        $cell->txn_id = $cell->order_no;
    }

    public function validTxnId($interfaceRia)
    {
        if(So::where("so_no",$interfaceRia["so_no"])->first())
        {
            return true;
        }
        return false;
    }
}
