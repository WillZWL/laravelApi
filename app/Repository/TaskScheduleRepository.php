<?php

namespace App\Repository;

use App\Models\TaskSchedule;

class TaskScheduleRepository
{
    public function getTask($name)
    {
        $object = TaskSchedule::Active()
            ->whereName($name)
            ->first();
        if ($object) {
            return $object;
        }
        return false;
    }

    public function lockTask($name)
    {
        return TaskSchedule::whereName($name)
            ->whereLock(0)
            ->whereStatus(1)
            ->update([
                'lock'=>1,
                'last_access_time'=>date("Y-m-d H:i:s")
            ]);
    }

    public function unlockTask($name)
    {
        return TaskSchedule::whereName($name)
            ->whereLock(1)
            ->whereStatus(1)
            ->update([
                'lock'=>0,
                'last_access_time'=>date("Y-m-d H:i:s")
            ]);
    }
}