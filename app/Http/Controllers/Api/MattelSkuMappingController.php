<?php

namespace App\Http\Controllers\Api;

use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Websafe\Blueimp\JqueryFileUploadHandler;
use App\Services\MattelSkuMappingService;


class MattelSkuMappingController extends Controller
{
    use Helpers;

    public function index()
    {

    }

    public function upload(Requests\MattelSkuMappingUploadRequest $mattelSkuMappingUploadRequest)
    {
        $upload_dir = storage_path() .'/mattel-sku-mapping-upload/';
        $options['upload_dir'] = $this->mkUploadDir($upload_dir);
        $options['print_response'] = false;
        try {
            $upload_response = new JqueryFileUploadHandler($options);
        } catch (\Exception $e) {
            mail('will.zhang@eservicesgroup.com', 'Mattel SKU Mapping Upload Failed - Exception', $e->getMessage()."\r\n File: ".$e->getFile()."\r\n Line: ".$e->getLine());
        }
        $response = $upload_response->response;
        if ($response) {
            $upload_file_path = $options['upload_dir'].$response['files'][0]->name;
            $mattelSkuMappingService = New MattelSkuMappingService();
            $mattelSkuMappingService->handleUploadFile($upload_file_path);
        }
        return response()->json($response);
    }

    public function mkUploadDir($dir)
    {
        if (!is_dir($dir.date('Y').'/')) {
            mkdir($dir.date('Y').'/', 775, true);
        }
        if (!is_dir($dir.date('Y').'/'.date('m').'/')) {
            mkdir($dir.date('Y').'/'.date('m').'/', 775, true);
        }
        if (!is_dir($dir.date('Y').'/'.date('m').'/'.date('d').'/')) {
            mkdir($dir.date('Y').'/'.date('m').'/'.date('d').'/', 775, true);
        }
        return $dir.'/'.date('Y').'/'.date('m').'/'.date('d').'/';
    }


    public function donwloadExampleFile($filename)
    {
        $file = \Storage::disk('mattelSkuMappingUpload')->get($filename);
        return response($file, 200)->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

}
