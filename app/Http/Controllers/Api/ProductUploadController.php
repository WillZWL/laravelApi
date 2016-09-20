<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Websafe\Blueimp\JqueryFileUploadHandler;
use App\Services\ProductService;

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
        $options['upload_dir'] = $this->mkUploadDir($upload_dir);
        $options['print_response'] = false;
        try {
            $upload_response = new JqueryFileUploadHandler($options);
        } catch (\Exception $e) {
            mail('will.zhang@eservicesgroup.com', 'Product Upload Failed - Exception', $e->getMessage()."\r\n File: ".$e->getFile()."\r\n Line: ".$e->getLine());
        }
        $response = $upload_response->response;
        if ($response) {
            $upload_file_path = $options['upload_dir'].$response['files'][0]->name;
            $productService = New ProductService();
            $productService->handleUploadFile($upload_file_path);
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
        $file = \Storage::disk('productUpload')->get($filename);
        return response($file, 200)->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function testExcelToDatabase()
    {

        $file = storage_path() .'/bulk-product-upload/2016/09/13/UploadExample.csv';

        $productService = New ProductService();

        $productService->handleUploadFile($file);

    }
}
