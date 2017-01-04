<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public $connection = 'mysql_esg';

    protected $table = 'role';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [];

    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }

}
