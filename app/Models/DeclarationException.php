<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeclarationException extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'declaration_exception';

    public $timestamps = false;

    public $incrementing = false;

    protected $guarded = ['create_at'];
}
