<?php

namespace App\Services;

//use SplEnum;

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
    }
    /***********************************
    *   If Refund, return 'R'
    *   If Chargeback return 'CB'
    *   return False otherwises
    ***********************************/
    public function isRefundRecord($cell)
    {
        return false;
    }
    public function isSoFeeRecord($cell)
    {
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
    protected function insertInterfaceFlexRia($batch_id, $status, $cell)
    {

    }
    protected function insertInterfaceFlexSoFee($batch_id, $status, $cell)
    {

    }
    protected function insertInterfaceFlexRefund($batch_id, $status, $cell)
    {

    }
    protected function insertInterfaceFlexRollingReserve($batch_id, $status, $cell)
    {

    }
    protected function insertInterfaceFlexGatewayFee($batch_id, $status, $cell)
    {

    }
    protected function afterInsertAllInterface($batch_id)
    {

    }

    /************************************
    *   For Paypay, The refund record need to
    *   1. create the interface_flex_refund
    *   2. create the interface_flex_so_fee
    *************************************/
    function insertSoFeeFromRefundRecord($batch_id, $status, $cell)
    {

    }
    function insertSoFeeFromRiaRecord($batch_id, $status, $cell)
    {

    }
    function insertSoFeeFromRollingReserveRecord($batch_id, $status, $cell)
    {

    }
}
