<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'quotation';

    public $timestamps = false;

    public $incrementing = false;

    public function courierInfo()
    {
        return $this->belongsTo('App\Models\CourierInfo', 'courier_id', 'courier_id');
    }

    public function getAcceleratorQuotationByProduct(Model $product)
    {
        $quotation = $product->merchantProductMapping->merchant->merchantQuotation;
        $activeQuotation = $quotation->filter(function ($item) {
            if ($item->current_used == 1
            && $item->is_approved == 1
            && $item->status == 1
            && $item->expire_date >= Carbon::today()
            && in_array($item->quotation_type, ['acc_builtin_postage', 'acc_external_postage', 'acc_courier', 'acc_courier_exp', 'acc_fbmp'])) {
                return true;
            }
        });

        return $activeQuotation->pluck('id', 'quotation_type');
    }

    public static function getQuotation(CountryState $destination, $weightId, $quotationVersionId)
    {
         return Quotation::whereQuotnVersionId($quotationVersionId)
            ->whereDestCountryId($destination->country_id)
            ->whereDestStateId($destination->state_id)
            ->whereWeightId($weightId)
            ->first();
    }
}
