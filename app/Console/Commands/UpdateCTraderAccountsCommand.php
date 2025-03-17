<?php

namespace App\Console\Commands;

use App\Models\TradingUser;
use Illuminate\Console\Command;
use App\Services\CTraderService;
use Illuminate\Support\Facades\Log;
use App\Services\Data\UpdateTradingUser;
use App\Services\Data\UpdateTradingAccount;

class UpdateCTraderAccountsCommand extends Command
{
    protected $signature = 'tradingUser:refresh_accounts';

    protected $description = 'Update cTrader accounts for active trading users';

    // Disable the Laravel command timeout
    protected $timeout = null;

    public function handle(): void
    {
        // Disable PHP execution timeout (unlimited time)
        ini_set('max_execution_time', 0);  // No timeout for PHP script execution
    
        $this->info('Starting to refresh cTrader accounts...');
        Log::info("Refreshing...");
        // Fetch all active accounts without chunking
        $trading_accounts = TradingUser::get();
    
        foreach ($trading_accounts as $account) {
            try {
                // Attempt to fetch user data
                $accData = (new CTraderService())->getUser($account->meta_login);
    
                // If no data is returned (null or empty), mark the account as inactive
                if (empty($accData)) {
                    Log::info("Inactive account {$account->meta_login}");
                    if ($account->acc_status !== 'inactive') {
                        $account->acc_status = 'inactive';
                        $account->save();
                        // $this->warn("Account {$account->meta_login} marked as inactive.");
                    }

                    $tradingAccount = $account->trading_account;
                    if ($tradingAccount) {
                        $tradingAccount->delete();
                    }
                    
                    $account->delete();

                } else {
                    // Proceed with updating account information
                    // Log::info("Refreshing {$account->meta_login}");
                    (new UpdateTradingUser)->execute($account->meta_login, $accData);
                    (new UpdateTradingAccount)->execute($account->meta_login, $accData);
                }
            } catch (\Exception $e) {
                // Log the error message
                Log::error("Failed to refresh account {$account->meta_login}: {$e->getMessage()}");
                // $this->error("Error processing account {$account->meta_login}: {$e->getMessage()}");
            }
        }
    
        // $this->info('Completed refreshing cTrader accounts.');
    }
}
