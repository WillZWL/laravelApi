<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Models\User;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function userList(Request $request)
    {
        //$result = $this->productService->skuMappingList($request->all());
        $result = User::where("status",1)->get();

        return response()->json($result);
    }
}
