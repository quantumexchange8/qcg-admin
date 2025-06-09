<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class SettingTicketScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [];

        foreach (range(1, 7) as $day) {
            $data[] = [
                'day' => $day,
                'start_time' => null,
                'end_time' => null,
                'status' => 'inactive',
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ];
        }

        DB::table('setting_ticket_schedules')->insert($data);
    }
}
