<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Websafe\Blueimp\JqueryFileUploadHandler;

class ProductUploadController extends Controller
{
    use Helpers;

    public function __construct()
    {

    }

    public function index()
    {

    }

    public function upload(Requests\ProductUploadRequest $updateProductRequest)
    {
        $upload_dir = storage_path() .'/bulk-product-upload/';
        if (!is_dir($upload_dir.'/'.date('Y').'/'.date('m').'/'.date('d').'/')) {
            $options['upload_dir'] = $this->mkUploadDir($upload_dir);
        } else {
            $options['upload_dir'] = $upload_dir.'/'.date('Y').'/'.date('m').'/'.date('d').'/';
        }
        $options['print_response'] = false;
        $upload_response = new JqueryFileUploadHandler($options);
        $response = $upload_response->response;
        return response()->json($response);
    }

    public function mkUploadDir($dir)
    {
        if (!is_dir($dir.date('Y').'/')) {
            mkdir($dir.date('Y').'/', 775, true);
            if (!is_dir($dir.date('Y').'/'.date('m').'/')) {
                mkdir($dir.date('Y').'/'.date('m').'/', 775, true);
                    if (!is_dir($dir.date('Y').'/'.date('m').'/'.date('d').'/')) {
                        mkdir($dir.date('Y').'/'.date('m').'/'.date('d').'/', 775, true);

                        return $dir.'/'.date('Y').'/'.date('m').'/'.date('d').'/';
                    }
            }
        }
    }
}
