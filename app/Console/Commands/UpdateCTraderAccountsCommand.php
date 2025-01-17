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
    protected $signature = 'refresh_accounts';

    protected $description = 'Update cTrader accounts for active trading users';

    public function handle(): void
    {
        $this->info('Starting to refresh cTrader accounts...');

        TradingUser::where('acc_status', 'active')
            ->chunk(100, function ($trading_accounts) {
                foreach ($trading_accounts as $account) {
                    try {
                        // Attempt to fetch user data
                        $accData = (new CTraderService())->getUser($account->meta_login);

                        // If no data is returned (null or empty), mark the account as inactive
                        if (empty($accData)) {
                            if ($account->acc_status !== 'inactive') {
                                $account->update(['acc_status' => 'inactive']);
                                // $this->warn("Account {$account->meta_login} marked as inactive.");
                            }
                        } else {
                            // Proceed with updating account information
                            (new UpdateTradingUser)->execute($account->meta_login, $accData);
                            (new UpdateTradingAccount)->execute($account->meta_login, $accData);
                        }
                    } catch (\Exception $e) {
                        // Log the error message
                        Log::error("Failed to refresh account {$account->meta_login}: {$e->getMessage()}");
                        // $this->error("Error processing account {$account->meta_login}: {$e->getMessage()}");
                    }
                }
            });

        // $this->info('Completed refreshing cTrader accounts.');
    }
}
