<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\AccountType;
use App\Models\Transaction;
use App\Models\TradingAccount;
use Illuminate\Console\Command;
use App\Services\CTraderService;
use Illuminate\Support\Facades\Log;
use App\Services\RunningNumberService;
use App\Services\Data\UpdateTradingUser;
use App\Services\Data\UpdateTradingAccount;

class UpdatePromotionStatus extends Command
{
    // Define the name and signature of the console command.
    protected $signature = 'accountType:updatePromotionStatus';

    // The console command description.
    protected $description = 'Check promotion account types and update their status if promotion period has ended';

    // Disable the Laravel command timeout
    protected $timeout = null;

    // Execute the console command.
    public function handle()
    {
        // Disable PHP execution timeout (unlimited time)
        ini_set('max_execution_time', 0);  // No timeout for PHP script execution

        // Get all account types where the category is 'promotion'
        $accountTypes = AccountType::where('category', 'promotion')->where('status', 'active')->get();

        if ($accountTypes->isEmpty()) {
            // $this->info('No account types found with category "promotion".');
            return;
        }

        $currentDate = Carbon::today()->toDateString(); // "YYYY-MM-DD"

        foreach ($accountTypes as $accountType) {
            // Check if the promotion period is of type 'specific_date_range'
            if ($accountType->promotion_period_type === 'specific_date_range' && $accountType->promotion_period) {
                // Compare the promotion end date with today's date
                $promotionEndDate = Carbon::parse($accountType->promotion_period)->toDateString(); // "YYYY-MM-DD"

                // Check if the promotion period ends today
                if ($promotionEndDate === $currentDate) {
                    // Update the account type to inactive
                    $accountType->status = 'inactive';
                    $accountType->save();

                    // Log the status update
                    Log::info('Updated AccountType: ' . $accountType->name . ' to inactive');

                    // Now, let's update the associated trading accounts
                    $this->updateTradingAccounts($accountType);
                }
            }
        }
    }

    /**
     * Update trading accounts associated with the given account type.
     *
     * @param AccountType $accountType
     * @return void
     */
    protected function updateTradingAccounts(AccountType $accountType)
    {
        // Find all trading accounts that are active and have the given account type
        $tradingAccounts = TradingAccount::where('status', 'active')
                                          ->where('account_type_id', $accountType->id) // Assuming 'account_type_id' is the foreign key
                                          ->get();
    
        // Update each trading account to inactive
        foreach ($tradingAccounts as $tradingAccount) {
            try {
                // Attempt to fetch user data
                $accData = (new CTraderService())->getUser($tradingAccount->meta_login);
    
                // If no data is returned (null or empty), mark the account as inactive
                if (empty($accData)) {
                    if ($tradingAccount->status !== 'inactive') {
                        $tradingAccount->update(['status' => 'inactive']);
                        if ($tradingAccount->trading_user) {
                            $tradingAccount->trading_user->update(['acc_status' => 'inactive']);
                        }
                    }
                } else {
                    // Proceed with updating account information
                    (new UpdateTradingUser)->execute($tradingAccount->meta_login, $accData);
                    (new UpdateTradingAccount)->execute($tradingAccount->meta_login, $accData);
    
                    // Check if the credit column is not zero
                    if ($tradingAccount->credit > 0) {
                        // Make the credit column zero
                        $credit = $tradingAccount->credit;
                        $tradingAccount->credit = 0;
    
                        // Ensure the related TradingUser credit is also set to zero
                        if ($tradingAccount->trading_user) {
                            $tradingAccount->trading_user->credit = 0;
                            $tradingAccount->trading_user->save();
                        }
    
                        $tradingAccount->save();  // Save the updated TradingAccount
    
                        // Create a transaction for the system withdrawal of the credit
                        $this->createCreditWithdrawalTransaction($tradingAccount, $credit);
                    }
                }
            } catch (\Exception $e) {
                // Log the error message
                Log::error("Failed to refresh promotion account {$tradingAccount->meta_login}: {$e->getMessage()}");
            }
        }
    }
    
    /**
     * Create a transaction to withdraw the credit.
     *
     * @param TradingAccount $tradingAccount
     * @param float $credit
     * @return void
     */
    protected function createCreditWithdrawalTransaction(TradingAccount $tradingAccount, $credit)
    {
        try {
            // Assuming you have a Transaction model with the required fields
            Transaction::create([
                'user_id' => $tradingAccount->user_id, // Assuming the user_id is present in TradingAccount model
                'category' => 'bonus',
                'transaction_type' => 'withdrawal',
                'transaction_number' => RunningNumberService::getID('transaction'), // Assuming RunningNumberService generates unique IDs
                'amount' => $credit,
                'transaction_charges' => 0,
                'transaction_amount' => $credit,
                'status' => 'successful',
                'remarks' => "System withdrawal of remaining credit",
                'approved_at' => now(), // Current time when the transaction is approved
            ]);

            // Log the transaction creation
            // $this->info("Created a withdrawal transaction for Account {$tradingAccount->meta_login} with amount {$credit}");
        } catch (\Exception $e) {
            // Log the error message for transaction creation
            Log::error("Failed to create withdrawal transaction for account {$tradingAccount->meta_login}: {$e->getMessage()}");
        }
    }
}
