<?php

namespace App\Console\Commands;

use App\Models\TradeBrokerHistory;
use App\Models\TradePointHistory;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UpdateTradePointsCommand extends Command
{
    // Define the name and signature of the console command.
    protected $signature = 'wallet:updateTradePoints';

    // The console command description.
    protected $description = 'Check previous day trade lots, convert to points and add to user wallets.';

    // Disable the Laravel command timeout
    protected $timeout = null;

    public function handle()
    {
        $this->info('Starting trade points update...');
        $walletUpdates = [];

        // Get previous day's date
        // $yesterday = Carbon::yesterday();
        $lastMonth = Carbon::now()->subMonth();

        // Retrieve trade lots grouped by user_id and symbol_group_id for the previous day
        $tradeLots = TradeBrokerHistory::selectRaw('db_user_id, db_symgroup_id, SUM(trade_lots) as total_trade_lots')
            ->whereMonth('trade_close_time', $lastMonth->month)
            ->whereYear('trade_close_time', $lastMonth->year)
            ->groupBy('db_user_id', 'db_symgroup_id')
            ->get();

        if ($tradeLots->isEmpty()) {
            $this->info('No trade lots found for the previous day.');
            return;
        }

        foreach ($tradeLots as $tradeLot) {
            $userId = $tradeLot->db_user_id;
            $points = $tradeLot->total_trade_lots; // Convert trade lots to points if needed
        
            // Accumulate trade points for the user
            $walletUpdates[$userId] = ($walletUpdates[$userId] ?? 0) + $points;
        
            // Insert the trade point history record
            TradePointHistory::create([
                'user_id' => $userId,
                'category' => 'trade_lots',
                'symbol_group_id' => $tradeLot->db_symgroup_id,
                'trade_points' => $tradeLot->total_trade_lots,
            ]);
        }

        foreach ($walletUpdates as $userId => $tradePoints) {
            if (!User::where('id', $userId)->exists()) {
                Log::warning("User ID {$userId} not found. Skipping wallet update.");
                continue;
            }
        
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $userId, 'type' => 'trade_points'],
                [
                    'address' => 'TP' . Str::padLeft($userId, 5, "0"),
                    'balance' => 0,
                ]
            );
        
            $wallet->increment('balance', $tradePoints);
        }

        $this->info('Trade points updated successfully and added to wallets.');
    }
}
