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
        
        if (isset($data['lastConnectionTimestamp'])) {
            $timestamp = (int) $data['lastConnectionTimestamp'];

            if ($timestamp > 9999999999) {
                $timestamp = $timestamp / 1000;
            }

            $tradingUser->last_access = Carbon::createFromTimestamp($timestamp)->toDateTimeString();
            Log::info("Refreshing last access for account {$meta_login} to {$tradingUser->last_access}");
        } else {
            $tradingUser->last_access = null;
        }
        
        $tradingUser->balance = $data['balance'] / 100;
        $tradingUser->credit = $data['nonWithdrawableBonus'] / 100;

        DB::transaction(function () use ($tradingUser) {
            $tradingUser->save();
        });

        return $tradingUser;
    }
}
