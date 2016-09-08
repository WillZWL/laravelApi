<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class LazadaAllocatedTransformer extends TransformerAbstract
{
    public function transform($result)
    {
        foreach($result as $soNo => $orderItem){
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
        return $data;
    }
}
