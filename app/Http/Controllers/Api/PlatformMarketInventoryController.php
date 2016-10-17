<?php

namespace App\Http\Controllers\Api;

use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Websafe\Blueimp\JqueryFileUploadHandler;
use App\Transformers\PlatformMarketInventoryTransformer;
use App\Services\PlatformMarketInventoryService;

class PlatformMarketInventoryController extends Controller
{
    use Helpers;

    private $inventoryService;


    public function __construct(PlatformMarketInventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $inventorys = $this->inventoryService->getSkuInventorys($request);

        return $this->response->paginator($inventorys, new PlatformMarketInventoryTransformer());
    }

    public function upload(Requests\UploadRequest $uploadRequest)
    {
        if ($uploadRequest->isMethod('post')) {
            $upload_dir = storage_path() .'/platform-market-inventory-upload/';
            $options['upload_dir'] = $this->mkUploadDir($upload_dir);
            $options['print_response'] = false;
            try {
                $upload_response = new JqueryFileUploadHandler($options);
                $response = $upload_response->response;
            } catch (\Exception $e) {
                mail('will.zhang@eservicesgroup.com', 'Platform Market Inventory Upload Failed - Exception', $e->getMessage()."\r\n File: ".$e->getFile()."\r\n Line: ".$e->getLine());
            }
            if ($response) {
                $upload_file_path = $options['upload_dir'].$response['files'][0]->name;
                $this->inventoryService->handleUploadFile($upload_file_path);
                return response()->json($response);
            }
        }
    }

    public function mkUploadDir($dir)
    {
        if (!is_dir($dir.date('Y').'/'.date('m').'/'.date('d').'/')) {
            mkdir($dir.date('Y').'/'.date('m').'/'.date('d').'/', 0775, true);
        }
        return $dir.'/'.date('Y').'/'.date('m').'/'.date('d').'/';
    }


    public function donwloadExampleFile($fileName)
    {
        $file = \Storage::disk('platformMarketInventoryUpload')->get($fileName);
        return response($file, 200)->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

}
