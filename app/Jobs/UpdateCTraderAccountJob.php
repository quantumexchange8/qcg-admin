<?php

namespace App\Jobs;

use App\Models\TradingUser;
use Illuminate\Bus\Queueable;
use App\Models\TradingAccount;
use App\Services\CTraderService;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateCTraderAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Job will run in a specific queue
    public function __construct()
    {
        $this->queue = 'refresh_accounts';
    }

    public function handle(): void
    {
        // Process accounts in batches of 100 (adjust as needed)
        TradingAccount::where('account_type_id', 1)
            ->chunk(100, function ($trading_accounts) {
                foreach ($trading_accounts as $account) {
                    try {
                        // Attempt to get user info from CTraderService
                        (new CTraderService())->getUserInfo($account->meta_login);
                    } catch (\Illuminate\Http\Client\RequestException $e) {
                        // Log the error message for 404 errors or other HTTP issues
                        Log::error("Failed to refresh account {$account->meta_login}: {$e->getMessage()}");
        
                        // If the error is a 404, update the acc_status to "inactive"
                        if ($e->response->status() == 404) {
                            TradingUser::where('meta_login', $account->meta_login)
                                ->update(['acc_status' => 'inactive']);
                        }
                    }
                }
            });
    }
}
