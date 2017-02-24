<?php

use Illuminate\Database\Seeder;

class TaskScheduleSeeders extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('task_schedules')->insert([
            'id' => 1,
            'name' => "CREATE_ALLOCATION_PLAN",
            'description' => "Create Allocation Plan",
            'lock' => 0,
            'last_access_time' => date("Y-m-d H:i:s"),
            'status' => 1,
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);
    }
}
