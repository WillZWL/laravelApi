<?php
namespace App\Http\Controllers;

use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Models\So;
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
        echo 1;
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
                      
                        if (copy($fileFrom.$oldName, $fileTo.$newName)) {
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
                $result['msg'].="no files in $fileFrom\n";
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
            }
        }
        var_dump($result);
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

