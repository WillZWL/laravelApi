<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Websafe\Blueimp\JqueryFileUploadHandler;
use App\Services\PriceUploadService;

class PriceUploadController extends Controller
{
    public function upload()
    {
        $upload_dir = storage_path() .'/bulk-price-upload/';
        $options['upload_dir'] = $this->mkUploadDir($upload_dir);
        $options['print_response'] = false;
        try {
            $upload_response = new JqueryFileUploadHandler($options);

            $response = $upload_response->response;

            if ($response) {
                $upload_file_path = $options['upload_dir'].$response['files'][0]->name;
                $priceUploadService = New PriceUploadService();
                $priceUploadService->handleUploadFile($upload_file_path);
            }
            return response()->json($response);
        } catch (\Exception $e) {
            mail('milo.chen@eservicesgroup.com', 'Price Upload Failed - Exception', $e->getMessage()."\r\n File: ".$e->getFile()."\r\n Line: ".$e->getLine());
        }
    }

    public function mkUploadDir($dir)
    {
        if (!is_dir($dir.date('Y').'/'.date('m').'/'.date('d').'/')) {
            mkdir($dir.date('Y').'/'.date('m').'/'.date('d').'/', 0775, true);
        }
        return $dir.'/'.date('Y').'/'.date('m').'/'.date('d').'/';
    }

    public function donwloadExampleFile($filename)
    {
        $file = \Storage::disk('priceUpload')->get($filename);
        return response($file, 200)->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
