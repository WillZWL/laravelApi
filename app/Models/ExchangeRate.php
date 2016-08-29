<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'exchange_rate';

    //public $primaryKey = ['from_currency_id', 'to_currency_id'];

    public $timestamps = false;

    public static function getRate($fromCurrency, $toCurrency)
    {
        return self::where('from_currency_id', '=', $fromCurrency)
            ->where('to_currency_id', '=', $toCurrency)
            ->firstOrFail()
            ->rate;
    }
}
