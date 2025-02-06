<?php

namespace App\Jobs;

use App\Models\TradingUser;
use Illuminate\Bus\Queueable;
use App\Services\CTraderService;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use App\Services\Data\UpdateTradingUser;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\Data\UpdateTradingAccount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateCTraderAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Set the timeout for the job to be infinite
    public $timeout = null;

    // Job will run in a specific queue
    public function __construct()
    {
        $this->queue = 'refresh_accounts';
    }

    public function handle(): void
    {
        // Process all active accounts at once (no chunking)
        $trading_accounts = TradingUser::where('acc_status', 'active')->get();

        foreach ($trading_accounts as $account) {
            try {
                // Attempt to fetch user data
                $accData = (new CTraderService())->getUser($account->meta_login);

                // If no data is returned (null or empty), mark the account as inactive
                if (empty($accData)) {
                    if ($account->acc_status !== 'inactive') {
                        $account->acc_status = 'inactive';
                        $account->save();
                    }

                    $tradingAccount = $account->trading_account;
                    if ($tradingAccount) {
                        $tradingAccount->delete();
                    }
                    
                    $account->delete();

                } else {
                    // Proceed with updating account information
                    (new UpdateTradingUser)->execute($account->meta_login, $accData);
                    (new UpdateTradingAccount)->execute($account->meta_login, $accData);
                }
            } catch (\Exception $e) {
                // Log the error message for issues such as network errors, timeouts, etc.
                Log::error("Failed to refresh account {$account->meta_login}: {$e->getMessage()}");
            }
        }
    }
}
