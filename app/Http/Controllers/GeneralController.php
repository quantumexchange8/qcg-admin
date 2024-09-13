<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
use App\Services\CTraderService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GeneralController extends Controller
{
    public function getWalletData(Request $request)
    {
        // Fetch wallets where user_id matches and type is 'rebate_wallet'
        $wallets = Wallet::where('user_id', $request->user_id)
                         ->where('type', 'rebate_wallet')
                         ->first();

        return response()->json([
            'walletData' => $wallets,
        ]);
    }

    public function getTradingAccountData(Request $request): Collection
    {
        // Check connection status
        $conn = (new CTraderService)->connectionStatus();
        if ($conn['code'] != 0) {
            return collect([
                'toast' => [
                    'title' => 'Connection Error',
                    'type' => 'error'
                ]
            ]);
        }
    
        // Fetch all trading accounts for the given user_id
        $accounts = TradingAccount::where('user_id', $request->user_id)->get();
    
        // Map each account to fetch the latest balance and credit
        $accountData = $accounts->map(function ($account) {
            try {
                // Fetch the latest account info using CTraderService
                try {
                    (new CTraderService)->getUserInfo($account->meta_login);
                } catch (\Throwable $e) {
                    Log::error($e->getMessage());
                }
    
                // Retrieve the latest account data after update
                $updatedAccount = TradingAccount::where('meta_login', $account->meta_login)->first();
    
                // Return the structured data for each account
                return [
                    'meta_login' => $updatedAccount->meta_login,
                    'balance' => $updatedAccount->balance,
                    'credit' => $updatedAccount->credit,
                ];
            } catch (\Throwable $e) {
                Log::error("Error processing account {$account->meta_login}: " . $e->getMessage());
    
                // In case of error, return account data with existing values
                return [
                    'meta_login' => $account->meta_login,
                    'balance' => 0,
                    'credit' => 0,
                ];
            }
        });
    
        return collect(['accountData' => $accountData]);
    }
}
