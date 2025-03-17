<?php

namespace App\Services\Data;

use App\Models\AccountType;
use App\Models\TradingUser;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateTradingUser
{
    public function execute($meta_login, $data): TradingUser
    {
        return $this->updateTradingUser($meta_login, $data);
    }

    public function updateTradingUser($meta_login, $data): TradingUser
    {
        $tradingUser = TradingUser::query()
            ->where('meta_login', $meta_login)
            ->first();

        $accountType = AccountType::query()
            ->where('account_group', $data['groupName'])
            ->first();

        $tradingUser->meta_group = $data['groupName'];
        $tradingUser->account_type_id = $accountType->id;
        $tradingUser->leverage = $data['leverageInCents'] / 100;
        $tradingUser->registration = $data['registrationTimestamp'];
        
        try {
            if (isset($data['lastConnectionTimestamp'])) {
                $timestamp = (int) $data['lastConnectionTimestamp'];
    
                if ($timestamp > 9999999999) {
                    $timestamp = $timestamp / 1000;
                }
    
                // $tradingUser->last_access = Carbon::createFromTimestamp($timestamp)->toDateTimeString();
                $tradingUser->forceFill(['last_access' => Carbon::createFromTimestamp($timestamp)->toDateTimeString()]); // Laravel Eloquent forcechange
                // Log::info("Refreshing last access for account {$meta_login} to {$tradingUser->last_access}");
            } else {
                $tradingUser->last_access = null;
            }
            
            $tradingUser->balance = $data['balance'] / 100;
            $tradingUser->credit = $data['nonWithdrawableBonus'] / 100;
            // Log::info('Dirty attributes:', $tradingUser->getDirty());
    
            $tradingUser->save();
    
            // if ($tradingUser->wasChanged('last_access')) {
            //     Log::info("Successfully updated last_access for {$tradingUser->meta_login}");
            // } else {
            //     Log::warning("Warning: last_access did not change for {$tradingUser->meta_login}");
            // }
        } catch (\Exception $e) {
            Log::error("Failed to update last_access for {$tradingUser->meta_login}: " . $e->getMessage());
        }
        // DB::transaction(function () use ($tradingUser) {
        //     try {
        //         Log::info("Dirty attributes before saving:", $tradingUser->getDirty());
                
        //         $tradingUser->save();
        
        //         if ($tradingUser->wasChanged('last_access')) {
        //             Log::info("Successfully updated last_access for {$tradingUser->meta_login}");
        //         } else {
        //             Log::warning("Warning: last_access did not change for {$tradingUser->meta_login}");
        //         }
        
        //         Log::info("Transaction saved successfully for {$tradingUser->meta_login}");
        //     } catch (\Exception $e) {
        //         Log::error("Transaction failed for {$tradingUser->meta_login}: " . $e->getMessage());
        //         throw $e;
        //     }
        // });

        return $tradingUser;
    }
}
