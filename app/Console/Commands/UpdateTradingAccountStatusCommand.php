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
        $inactiveThreshold = now()->subDays(90);
        $cTraderService = new CTraderService(); // Initialize the CTraderService

        // Fetch all trading accounts
        $tradingAccounts = TradingAccount::with([
            'trading_user', 
            'transactions' => function ($query) use ($inactiveThreshold) {
                $query->whereIn('transaction_type', ['deposit', 'withdrawal'])
                      ->where('created_at', '>=', $inactiveThreshold)
                      ->latest() // Sort by created_at descending
                      ->limit(1); // Limit to the most recent transaction
            }
        ])->get();

        foreach ($tradingAccounts as $account) {
            // Ensure that we refresh the user data before processing
            if ($account->trading_user) {
                $cTraderService->getUserInfo($account->trading_user->meta_login); // Update user data
            }

            // Get the latest transaction if it exists
            $lastTransaction = $account->transactions->first();
            
            // Check if the account has any positive balances
            $hasPositiveBalance = $account->balance > 0 || $account->equity > 0 || $account->credit > 0 || $account->cash_equity > 0;

            // Determine if the account is active
            // If there is a positive balance, the account stays active regardless of other factors
            $isActive = $hasPositiveBalance || 
                        ($lastTransaction && $lastTransaction->created_at >= $inactiveThreshold) ||
                        ($account->trading_user->last_access && $account->trading_user->last_access >= $inactiveThreshold);

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
