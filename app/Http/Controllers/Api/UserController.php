<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Models\User;
use App\Models\Role;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function userList(Request $request)
    {
        $data = $request->all();
        //$result = User::where("id","milo")->get();

        $result = User::join('user_role', 'user.id', '=', 'user_role.user_id')
                        ->where("user.status",1)
                        ->whereIn("user_role.role_id",$data["role"])
                        ->select("user.id","user.username","user_role.role_id")
                        ->get();

        return response()->json($result);
    }
}

