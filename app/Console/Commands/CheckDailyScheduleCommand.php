<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\Reward;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckDailyScheduleCommand extends Command
{
    protected $signature = 'check:daily-schedule';

    protected $description = 'Check daily setting schedule, activates announcements or rewards if date met.';

    public function handle(): void
    {
        $this->info('Starting daily schedule update...');
        $today = Carbon::today();
        // $this->info($today);
        $announcements = Announcement::whereIn('status', ['active', 'scheduled'])->get();

        foreach ($announcements as $announcement) {
            if ($announcement->status == 'active' && ($announcement->end_date && $announcement->end_date < $today)) {
                $announcement->update(['status' => 'expired']);
                $this->info('Closing an announcement...');
                continue;
            }

            if ($announcement->status == 'scheduled' && $announcement->start_date <= $today) {
                $announcement->update(['status' => 'active']);
                $this->info('Starting an announcement...');
            }
        }

        $rewards = Reward::where('status', 'active')->where('autohide_after_expiry', true)->get();
        foreach ($rewards as $reward) {
            if ($reward->expiry_date < $today) {
                // $this->info($reward->expiry_date);
                // $this->info($today);
                $reward->update(['status' => 'inactive']);
                $this->info('Closing reward ' . $reward->code);
                continue;
            }
        }

        $this->info('Finished daily schedule update.');
    }
}
