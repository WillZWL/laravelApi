<?php
namespace App\Http\Controllers\Api;

use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Validator;
use Excel;

class ReportController extends Controller
{
    use Helpers;

    public function __construct()
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      //
    	$cellData = [
			['学号','姓名','成绩'],
			['10001','AAAAA','99'],
			['10002','BBBBB','92'],
			['10003','CCCCC','95'],
			['10004','DDDDD','89'],
			['10005','EEEEE','96'],
		];
		$cellData2 = [
			['学号','姓名','成绩'],
			['10001','AAAAA','990'],
			['10002','BBBBB','92'],
			['10003','CCCCC','95'],
			['10004','DDDDD','89'],
			['10005','EEEEE','96'],
		];
		Excel::create('学生成绩',function($excel) use ($cellData,$cellData2){
			$excel->sheet('score', function($sheet) use ($cellData){				
				$sheet->rows($cellData);
				$sheet->setAutoFilter();
			});
			$excel->sheet('score2', function($sheet) use ($cellData2){
				$sheet->rows($cellData2);
			});
		})->export('xls');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Requests\Product\CreateRequest $request)
    {
        $result = $this->productService->store($request->all());

        return response()->json($result);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $sku
     * @return \Illuminate\Http\Response
     */
    public function show($sku, $lang='en')
    {
        $product = $this->productService->getProduct($sku);

        return $this->item($product, new productTransformer($lang));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $sku
     * @return \Illuminate\Http\Response
     */
    public function edit($sku)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Illuminate\Http\Request  $request
     * @param  string  $sku
     * @return \Illuminate\Http\Response
     */
    public function update(Requests\Product\UpdateRequest $request, $sku)
    {
        $result = $this->productService->update($request->all(), $sku);

        return response()->json($result);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $sku
     * @return \Illuminate\Http\Response
     */
    public function destroy($sku)
    {
        //
    }



}

