<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use Illuminate\Support\Collection;

class LazadaAllocatedTransformer extends TransformerAbstract
{
    static function transform(Collection $response)
    {
        if($response->result["response"]=="success"){
            foreach($response->result["message"] as $soNo => $orderItem){
                if($orderItem) {
                    $status="success";
                }else{
                    $status="false";
                }
                $data[] = array(
                    "so_no" => $soNo,
                    "status" => $status,
                    );
            }
        }else{
            $data = $response->result;
        }
        return $data;
    }
}
