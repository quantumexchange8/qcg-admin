<?php

namespace App\Console\Commands;

use App\Models\TradingAccount;
use App\Models\User;
use App\Services\CTraderService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UpdateAllCommand extends Command
{
    // Define the name and signature of the console command.
    protected $signature = 'admin:updateAll';

    // The console command description.
    protected $description = 'Update All Command (NOT SCHEDULE) : Changes depending on requirement; Currently update all accounts report_status to true.';

    // Disable the Laravel command timeout
    protected $timeout = null;

    public function handle()
    {
        $this->info('Starting trading report status update...');

        $tradingAccounts = TradingAccount::get();

        foreach ($tradingAccounts as $tradingAccount) {

            if (!User::where('id', $tradingAccount->user_id)->exists()) {
                Log::warning("User ID {$tradingAccount->user_id} not found. Skipping account update.");
                continue;
            }
            (new CTraderService())->changeReportStatus($tradingAccount->meta_login, true);

        }

        $this->info('Added daily trading report to all available accounts.');
    }
}
