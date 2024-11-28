<?php

namespace App\Console\Commands;

use App\Models\TradingAccount;
use Illuminate\Console\Command;
use App\Services\CTraderService;

class UpdateTradingAccountStatusCommand extends Command
{
    protected $signature = 'tradingAccount:check-status';

    protected $description = 'Check trading account activity and update statuses';

    public function handle()
    {
        $inactiveThreshold = now()->subDays(90)->startOfDay();

        $cTraderService = new CTraderService(); // Initialize the CTraderService
    
        // Fetch all trading accounts
        $tradingAccounts = TradingAccount::with([
            'trading_user', 
        ])->get();
    
        foreach ($tradingAccounts as $account) {
            // Ensure that we refresh the user data before processing
            if ($account->trading_user) {
                $cTraderService->getUserInfo($account->trading_user->meta_login); // Update user data
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
    
            // Update trading user status only if it has changed
            if ($account->trading_user && $account->trading_user->acc_status !== ($isActive ? 'active' : 'inactive')) {
                $account->trading_user->acc_status = $isActive ? 'active' : 'inactive';
                $account->trading_user->save(); // Save only if there is a change
            }
        }
        
        // Optionally, log completion or output
        // $this->info('Trading account statuses updated successfully.');
    }
}
