<?php

namespace App\Console\Commands;

use App\Models\TradingUser;
use App\Models\TradingAccount;
use Illuminate\Console\Command;
use App\Services\CTraderService;
use Illuminate\Support\Facades\Log;

class UpdateTradingAccountStatusCommand extends Command
{
    protected $signature = 'tradingAccount:check-status';

    protected $description = 'Check trading account activity and update statuses';

    public function handle()
    {
        $inactiveThreshold = now()->subDays(90)->startOfDay();

        $cTraderService = new CTraderService(); // Initialize the CTraderService
    
        // Fetch all trading accounts
        $tradingAccounts = TradingAccount::get();
    
        foreach ($tradingAccounts as $account) {
            // Refresh account data directly if needed
            try {
                $cTraderService->getUserInfo($account->meta_login); // Update user data (if needed)
            } catch (\Exception $e) {
                // Handle exception if refreshing user data fails
                Log::error("Failed to refresh account {$account->meta_login}: {$e->getMessage()}");

                // Handle "Not found" case, updating the acc_status to "inactive"
                if ($e->getMessage() == "Not found") {
                    // Update TradingUser acc_status to "inactive"
                    TradingUser::firstWhere('meta_login', $account->meta_login)->update(['acc_status' => 'inactive']);
                }
                
            }
    
            // Get the latest transaction if it exists
            // Access the latest transaction directly from transactions
            $lastTransaction = $account->transactions()
                                            ->whereIn('transaction_type', ['deposit', 'withdrawal'])
                                            ->where('created_at', '>=', $inactiveThreshold)
                                            ->latest()  // Order by latest transaction
                                            ->first();  // Get the latest transaction (or null if none)
    
            // Check if the account has any positive balances
            $hasPositiveBalance = $account->balance > 0 || $account->equity > 0 || $account->credit > 0 || $account->cash_equity > 0;
    
            // Check if the account's creation date is within the last 90 days
            $isRecentlyCreated = $account->created_at >= $inactiveThreshold;
    
            // Determine if the account is active
            $isActive = $isRecentlyCreated || 
                        $hasPositiveBalance || 
                        ($lastTransaction && $lastTransaction->created_at >= $inactiveThreshold);
    
            // Only update if the status has changed
            if ($account->status !== ($isActive ? 'active' : 'inactive')) {
                // Update account status
                $account->status = $isActive ? 'active' : 'inactive';
                $account->save(); // Save only if there is a change
            }
    
        }
        
        // Optionally, log completion or output
        // $this->info('Trading account statuses updated successfully.');
    }
}
