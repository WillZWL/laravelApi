<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceAlertEmail extends Model
{
    protected $table = 'marketplace_alert_email';

    public $primaryKey = 'id';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];

}
