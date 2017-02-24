<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskSchedule extends Model
{
    public function scopeActive($query)
    {
        return $query->where('task_schedules.status', '=', 1);
    }
}
