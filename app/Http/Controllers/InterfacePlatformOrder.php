<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApiPlatformFactoryService;
use PDF;

class InterfacePlatformOrder extends Controller
{
    public function __construct()
    {
       
    }

    public function index()
    {
        return PDF::loadFile('http://www.github.com')->inline('github.pdf');
    }
}
