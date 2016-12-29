<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'user';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [];

}
