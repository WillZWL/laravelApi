<?php
namespace App\Http\Controllers;

use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\So;
use App\Models\Config;
use App\Services\FlexService;
use Validator;
use Excel;
use Redirect;

class GatewayController extends Controller
{
    use Helpers;

    public function __construct()
    {
       
    }

    public function downloadGatewayReport(Request $request)
    {
        set_time_limit(0);
        $flexService = new FlexService();
        $response = $flexService->generateFeedbackReport($request);
    }

    /**
     * process gateway upload file
     *
     * @param  
     * @return \Illuminate\Http\Response
     */
    public function uploadGatewayReport(Request $request)
    {
        $pmgw = $request->pmgw;
        $email = $request->email;
        if(!$pmgw){ return false;}
        
        $fileFrom = Config::find("flex_ftp_location")->value."pmgw/".$pmgw."/";
        $fileTo = Config::find("flex_pmgw_report_loaction")->value.$pmgw."/";
        $result = ["status"=>false,"msg"=>""];
        if (is_dir($fileFrom)) {
            $fileArr = $this->getFileList($fileFrom);
            $result['status'] = TRUE;
            if(count($fileArr) > 0)
            {
                $flexService = new FlexService();
                foreach ($fileArr AS $key=>$oldName) {
                    $batchResult = TRUE;
                    if(!in_array($oldName, array(".", ".."))) {
                        
                        $newName = pathinfo(trim($oldName), PATHINFO_FILENAME)."_".date("YmdHis").".".pathinfo(trim($oldName), PATHINFO_EXTENSION);
                      
                        if (rename($fileFrom.$oldName, $fileTo.$newName)) {
                            list($batchResult, $batchIdList[]) =$flexService->processReport($pmgw, $newName,$email);

                            if($batchResult == FALSE &&  $result['status'] == TRUE) {
                                 $result['status'] = FALSE;
                            }
                        } else {
                            $result['status'] = FALSE;
                            $result['msg'].="failed to copy $oldName.\n";
                        }
                    }
                }
                if($result['status'] == TRUE)
                    $result['batchIdList'] = $batchIdList;
            }
            else
            {
                $result['status'] = FALSE;
                $result['msg'].="no files in $fileFrom";
            }
        }
        else
        {
            //create dir
            mkdir($fileFrom, 775, true);
            if(!is_dir($fileTo))
            { 
                mkdir($fileTo, 775, true); 
                mkdir($fileTo."/complete", 775, true);
            }
            $result['status'] = false;
            $result['msg'] = "invalid ftp file path";
        }

        if ($pmgw != 'paypal_pp' && $result['status']) {

            if (strpos($pmgw,'amazon') !== false) //amazon report
            {
                $request->batchIdList = $result['batchIdList'];
                $this->downloadGatewayReport($request);
            }else if(strpos($pmgw,'lazada') !== false)
            {
                $request->batchIdList = $result['batchIdList'];
                $this->downloadGatewayReport($request);
            }
        }

        if($result['status'] == FALSE)
        {
             echo "<script>alert('".$result['msg']."');history.back();</script>";
        }
    }


    public function getFileList($fileFrom)
    {
        $fileArr = scandir($fileFrom);

        $fileList = array();
        foreach($fileArr AS $key=>$oldName)
        {
            if(!in_array($oldName, array(".", "..")))
            {
                $fileList[] = $oldName;
            }
        }
        return $fileList;
    }

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
            
            if($data->count() <= 0)
            {
                return "<script>alert('file format error');history.back();</script>";
            }
            $error = [];
            foreach ($data as $key => $row) { 
                $so_no = $row->so_number;
                if(!$so_no)
                {
                    return "<script>alert('file data format error, do not read so number');history.back();</script>";
                }
                if(!$row->settlement_date)
                {
                    return "<script>alert('file data format error, do not read so settlement_date');history.back();</script>";
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

