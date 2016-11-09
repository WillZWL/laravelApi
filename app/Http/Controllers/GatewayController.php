<?php
namespace App\Http\Controllers;

use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\So;
use Validator;
use Excel;
use Redirect;

class GatewayController extends Controller
{
    use Helpers;

    public function __construct()
    {
       
    }

    /**
    * Upload Settlement Date with Order No
    * @return csv file or js 
    */
    public function uploadSettlement(Request $request)
    {
        $file = $request->csv;

        if(!$file || $file->getError()>0)
        {
            return "<script>alert('file upload error');history.back();</script>";
        }
        else
        {
            $data = Excel::load($file->getPathName())->get();
            if(!$data)
            {
                return "<script>alert('file format error');history.back();</script>";
            }
            $error = [];
            foreach ($data as $key => $row) { 
                $so_no = $row->order_number;
                if(!$so_no)
                {
                    return "<script>alert('file data format error');history.back();</script>";
                }
                $so = So::find($so_no);
                if($so)
                {
                    if($so->settlement_date == "0000-00-00")
                    {
                        $res = So::where("so_no",$so_no)->update(['settlement_date' => date("Y-m-d",strtotime($row->settlement_date))]);
                        if(!$res)
                        {
                            $error[] = [
                            "so_no"=>$so_no,
                            "reason"=>"update settlement_date error"
                            ];
                        }
                    }
                    else
                    {
                        $error[] = [
                            "so_no"=>$so_no,
                            "reason"=>"so already have settlement_date (".$so->settlement_date.")"
                            ];
                    }
                }
                else
                {
                    $error[] = [
                            "so_no"=>$so_no,
                            "reason"=>"so not found"
                            ];
                }               
            }

            if(count($error)>0)
            {
                $cellData[] = ["So Number","reason"];
                $cellData = array_merge($cellData,$error);
                Excel::create('feedback',function($excel) use ($cellData){
                  $excel->sheet('feedback', function($sheet) use ($cellData){                
                      $sheet->rows($cellData);
                  });
                })->export('csv');                
            }
            else
            {
                return "<script>alert('all success');history.back();</script>";
            }
        }
    }

    /**
    *  get order no from upload marketplace transaction ID 
    * @return csv file or js 
    */
    public function uploadTransaction(Request $request)
    {
        $transaction = $request->transaction;

        $transaction = array_map('trim', preg_split('/\r\n|\r|\n|,|;/', $transaction, -1, PREG_SPLIT_NO_EMPTY));
        if(!$transaction)
        {
            return "<script>alert('transaction format error');history.back();</script>";
        }
        $soList = So::join('selling_platform', 'selling_platform.id', '=', 'so.platform_id')
                    ->where('so.platform_group_order', "1")
                    ->where('selling_platform.type','ACCELERATOR')
                    ->where('so.status','<>',0)
                    ->where(function ($query) use ($transaction) {
                        $query->whereIn('so.txn_id', $transaction)
                              ->orWhereIn('so.platform_order_id', $transaction);
                    })->get();
                 
        if(count($soList) == 0)
        {
            return "<script>alert('so not found');history.back();</script>";
        }
        $cellData[] = ["Transaction ID","So Number"];
        foreach($soList as $so)
        {
            if( in_array(trim($so->txn_id),$transaction))
            {
                $cellData[] = [$so->txn_id,$so->so_no];   
            }
            else if( in_array(trim($so->platform_order_id),$transaction) )
            {
                $cellData[] = [$so->platform_order_id,$so->so_no];
            }
        }
        Excel::create('feedback',function($excel) use ($cellData){
                  $excel->sheet('so', function($sheet) use ($cellData){                
                      $sheet->rows($cellData);
                  });
                })->export('csv');  
    }
  
}

