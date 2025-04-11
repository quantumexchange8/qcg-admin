<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class SettingAutoApprovalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [];

        foreach (range(1, 7) as $day) {
            $data[] = [
                'type' => 'deposit',
                'day' => $day,
                'start_time' => null,
                'end_time' => null,
                'spread_amount' => null,
                'status' => 'inactive',
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ];
        }

        DB::table('setting_auto_approvals')->insert($data);
    }
}
