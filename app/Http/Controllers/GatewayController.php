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
                  $excel->sheet('score', function($sheet) use ($cellData){                
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
  
}

