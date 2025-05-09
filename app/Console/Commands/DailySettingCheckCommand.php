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

    protected $description = 'Check daily setting schedule, activates settings if date met.';

    public function handle(): void
    {
        $this->info('Starting daily schedule update...');
        $announcements = Announcement::where('status', ['active', 'scheduled'])
            ->get();

        // foreach ($announcement as $announcements) {
        //     if ($transaction->status == 'successful' && $transaction->transaction_type == 'deposit') {
        //         $trade = null;
        //         try {
        //             $trade = (new CTraderService)->createTrade($transaction->to_meta_login, $transaction->transaction_amount, "Deposit balance", 'DEPOSIT');
        //         } catch (\Throwable $e) {
        //             Log::error($e->getMessage());
        //         }
        //         $ticket = $trade->getTicket();
        //         $transaction->ticket = $ticket;
        //         $transaction->save();

        //         Notification::route('mail', 'payment@currenttech.pro')
        //             ->notify(new DepositApprovalNotification($transaction));
        //     }
        // }
    }
}
