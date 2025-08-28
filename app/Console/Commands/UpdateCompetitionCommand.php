<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\CompetitionReward;
use App\Models\Participant;

use App\Services\CTraderService;
use App\Models\TradeBrokerHistory;
use App\Models\TradingAccount;

// use App\Models\TradePointHistory;
// use App\Models\TradePointDetail;
// use App\Models\TradePointPeriod;
// use App\Models\Wallet;
// use App\Models\User;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UpdateCompetitionCommand extends Command
{
    // Define the name and signature of the console command.
    protected $signature = 'competition:updateRankings';

    // The console command description.
    protected $description = 'Check participant scores every 4 hours, update their rankings.';

    // Disable the Laravel command timeout
    protected $timeout = null;

    public function handle()
    {
        $this->info('Starting competition check...');
        // $walletUpdates = [];

        Log::info('Current Time : ' . Carbon::now());
        
        $ongoingCompetitions = Competition::all()->filter(function ($competition) {
            return $competition->statusByDate === 'ongoing';
        });

        foreach ($ongoingCompetitions as $ongoingCompetition) {
            // Log::info('Current Competition Category: ' . $ongoingCompetition->category);

            $histories = TradeBrokerHistory::whereBetween(
                'trade_close_time',
                [$ongoingCompetition->start_date, $ongoingCompetition->end_date]
            )
            ->whereHas('tradingAccount', function ($query) {
                $query->whereHas('account_type', function ($query) {
                    $query->where('account_group', 'COMPETITION');
                });
            })
            ->get();
            
            $groupedHistories = $histories->groupBy('meta_login');

            foreach ($groupedHistories as $meta_login => $accountHistories) {
                $score = 0;
            
                if ($ongoingCompetition->category === 'profit_rate') {
                    $capital = Transaction::where('transaction_type', 'deposit')->where('to_meta_login', $meta_login)->sum('transaction_amount');

                    (new CTraderService)->getUserInfo($account->meta_login);
                    $updatedAccount = TradingAccount::where('meta_login', $account->meta_login)->first();
                    $current_balance = $updatedAccount->balance - $updatedAccount->credit;

                    $score = $current_balance / $capital * 100.00;
                }
                elseif ($ongoingCompetition->category === 'trade_position') {
                    $score = $accountHistories->count();
                } elseif ($ongoingCompetition->category === 'trade_lot') {
                    $score = $accountHistories->sum('trade_lots');
                }

                $ongoingCompetition->participants()->updateOrCreate(
                    [
                        'competition_id' => $ongoingCompetition->id,
                        'user_type' => 'standard',
                        'meta_login' => $meta_login,
                    ],
                    [
                        'score' => $score,
                    ]
                );
            }
        }

        $this->info('Competition ranks have been updated successfully');
    }
}
