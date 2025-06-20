<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Inertia\Inertia;
use App\Models\AccountType;
use App\Models\TeamHasUser;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
use Illuminate\Support\Carbon;
use App\Services\CTraderService;
use App\Models\TradeBrokerHistory;
use App\Models\TradeLotSizeVolume;
use App\Models\TradeRebateSummary;
use App\Models\TotalPnlBroker;
use App\Jobs\CashWalletTransferJob;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/Dashboard', [
            'months' => (new GeneralController())->getTradeMonths(true),
            'teamMonths' => (new GeneralController())->getTransactionMonths(true),
        ]);
    }

    public function getPendingCounts()
    {
        $pendingWithdrawals = Transaction::whereNotIn('category', ['incentive_wallet', 'bonus_wallet'])
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'processing')
            ->count();

        $pendingBonus = Transaction::where('category', 'bonus')
            ->where('transaction_type', 'credit_bonus')
            ->where('status', 'processing')
            ->count();

        $pendingIncentive = Transaction::where('category', 'incentive_wallet')
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'processing')
            ->count();

        $pendingRewards = Transaction::where('category', 'trade_points')
            ->where('transaction_type', 'redemption')
            ->where('status', 'processing')
            ->count();

        $pendingKyc = User::where('kyc_approval', 'pending')->count();

        return response()->json([
            'pendingWithdrawals' => $pendingWithdrawals,
            'pendingBonus' => $pendingBonus,
            'pendingIncentive' => $pendingIncentive,
            'pendingRewards' => $pendingRewards,
            'pendingKyc' => $pendingKyc,
        ]);
    }

    public function getDashboardData()
    {
        $total_deposit = Transaction::whereIn('transaction_type', ['deposit', 'rebate_in', 'balance_in', 'credit_in'])
            ->where('status', 'successful')
            ->where(function ($query) {
                $query->whereHas('toMetaLogin', function ($q) {
                    $q->whereHas('accountType', function ($q2) {
                        $q2->where('account_group', 'STANDARD.t');
                    });
                })
                ->orWhere(function ($q) {
                    $q->whereNull('to_meta_login')
                    ->orWhereDoesntHave('toMetaLogin');
                });
            })
            ->sum('transaction_amount');
            
        $total_withdrawal = Transaction::whereIn('transaction_type', ['withdrawal', 'rebate_out', 'balance_out', 'credit_out'])
            ->where('status', 'successful')
            ->where(function ($query) {
                $query->whereHas('fromMetaLogin', function ($q) {
                    $q->whereHas('accountType', function ($q2) {
                        $q2->where('account_group', 'STANDARD.t');
                    });
                })
                ->orWhere(function ($q) {
                    $q->whereNull('from_meta_login')
                    ->orWhereDoesntHave('fromMetaLogin');
                });
            })
            ->sum('amount');

        $total_agent = User::where('role', 'agent')->count();

        $total_member = User::where('role', 'member')->count();

        $today_deposit = Transaction::whereIn('transaction_type', ['deposit', 'rebate_in', 'balance_in', 'credit_in'])
            ->where('status', 'successful')
            ->whereDate('created_at', today())
            ->where(function ($query) {
                $query->whereHas('toMetaLogin', function ($q) {
                    $q->whereHas('accountType', function ($q2) {
                        $q2->where('account_group', 'STANDARD.t');
                    });
                })
                ->orWhere(function ($q) {
                    $q->whereNull('to_meta_login')
                    ->orWhereDoesntHave('toMetaLogin');
                });
            })
            ->sum('transaction_amount');

        $today_withdrawal = Transaction::whereIn('transaction_type', ['withdrawal', 'rebate_out', 'balance_out', 'credit_out'])
            ->where('status', 'successful')
            ->whereDate('created_at', today())
            ->where(function ($query) {
                $query->whereHas('fromMetaLogin', function ($q) {
                    $q->whereHas('accountType', function ($q2) {
                        $q2->where('account_group', 'STANDARD.t');
                    });
                })
                ->orWhere(function ($q) {
                    $q->whereNull('from_meta_login')
                    ->orWhereDoesntHave('fromMetaLogin');
                });
            })
            ->sum('amount');

        $today_agent = User::where('role', 'agent')->whereDate('created_at', today())->count();

        $today_member = User::where('role', 'member')->whereDate('created_at', today())->count();

        return response()->json([
            'totalDeposit' => $total_deposit,
            'totalWithdrawal' => $total_withdrawal,
            'totalAgent' => $total_agent,
            'totalMember' => $total_member,
            'todayDeposit' => $today_deposit,
            'todayWithdrawal' => $today_withdrawal,    
            'todayAgent' => $today_agent,
            'todayMember' => $today_member,
        ]);
    }

    public function getAccountData()
    {
        $from = '2020-01-01T00:00:00.000';
        $to = now()->format('Y-m-d\TH:i:s.v');

        if (App::environment('production')) {
            // Ensure account type group IDs are updated before fetching the trader data
            (new CTraderService)->getAccountTypeGroupIds();  // Update account type group IDs
        }

        // Standard Account and Premium Account group IDs
        $groupIds = AccountType::whereNotNull('account_group_id')
            ->pluck('account_group_id')
            ->toArray();

        foreach ($groupIds as $groupId) {
            // Fetch data for each group ID
            $response = (new CTraderService)->getMultipleTraders($from, $to, $groupId);

            // Find the corresponding AccountType model
            // $accountType = AccountType::where('account_group_id', $groupId)->first();

            $accountType = AccountType::where('account_group', 'STANDARD.t')->first();

            // Initialize or reset group balance and equity
            $groupBalance = 0;
            $groupEquity = 0;

            $meta_logins = TradingAccount::where('account_type_id', $accountType->id)->pluck('meta_login')->toArray();

            // Assuming the response is an associative array with a 'trader' key
            if (isset($response['trader']) && is_array($response['trader'])) {
                foreach ($response['trader'] as $trader) {
                    if (in_array($trader['login'], $meta_logins)) {
                        // Determine the divisor based on moneyDigits
                        $moneyDigits = isset($trader['moneyDigits']) ? (int)$trader['moneyDigits'] : 0;
                        $divisor = $moneyDigits > 0 ? pow(10, $moneyDigits) : 1; // 10^moneyDigits

                        // Adjust balance and equity based on the divisor
                        $groupBalance += $trader['balance'] / $divisor;
                        $groupEquity += $trader['equity'] / $divisor;
                    }
                }

                // Update account group balance and equity
                $accountType->account_group_balance = $groupBalance;
                $accountType->account_group_equity = $groupEquity;
                $accountType->save();
            }
        }

        // Recalculate total balance and equity from the updated account types
        $totalBalance = AccountType::where('account_group', 'STANDARD.t')->sum('account_group_balance');
        $totalEquity = AccountType::where('account_group', 'STANDARD.t')->sum('account_group_equity');

        // Return the total balance and total equity as a JSON response
        return response()->json([
            'totalBalance' => $totalBalance,
            'totalEquity' => $totalEquity,
        ]);
    }

    public function getPendingData()
    {
        $pending_withdrawal = Transaction::whereNotIn('category', ['incentive_wallet', 'bonus'])
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'processing');

        $pending_bonus = Transaction::where('category', 'bonus')
            ->where('transaction_type', 'credit_bonus')
            ->where('status', 'processing');

        $pending_incentive = Transaction::where('category', 'incentive_wallet')
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'processing');

        $pendingRewards = Transaction::where('category', 'trade_points')
        ->where('transaction_type', 'redemption')
        ->where('status', 'processing')
        ->count();

        $pendingKyc = User::where('kyc_approval', 'pending')->count();

        return response()->json([
            'pendingWithdrawal' => $pending_withdrawal->sum('transaction_amount'),
            'pendingBonus' => $pending_bonus->sum('transaction_amount'),
            'pendingIncentive' => $pending_incentive->sum('transaction_amount'),
            'pendingWithdrawalCount' => $pending_withdrawal->count(),
            'pendingBonusCount' => $pending_bonus->count(),
            'pendingIncentiveCount' => $pending_incentive->count(),
            'pendingRewards' => $pendingRewards,
            'pendingKyc' => $pendingKyc,
        ]);
    }

    public function getTradeLotVolume(Request $request)
    {
        // Get the selected month (in format "m/Y")
        $monthYear = $request->input('selectedMonth');
    
        if ($monthYear === 'select_all') {
            $totals = TradeLotSizeVolume::selectRaw("
                tlv_year, tlv_month,
                COALESCE(SUM(CASE WHEN tlv_day = 0 THEN tlv_lotsize END), SUM(tlv_lotsize)) AS total_trade_lots,
                COALESCE(SUM(CASE WHEN tlv_day = 0 THEN tlv_volume_usd END), SUM(tlv_volume_usd)) AS total_volume
            ")
            ->where('tlv_group', 'STANDARD.t')
            ->groupBy('tlv_year', 'tlv_month')
            ->get();

            $totalTradeLots = $totals->sum('total_trade_lots');
            $totalVolume = $totals->sum('total_volume');
        } elseif (str_starts_with($monthYear, 'last_')) {
            preg_match('/last_(\d+)?_?week?/', $monthYear, $matches);
            $weekNumber = $matches[1] ?? 1;

            $startOfWeek = Carbon::now()->subWeeks($weekNumber)->startOfWeek();
            $endOfWeek = Carbon::now()->subWeeks($weekNumber)->endOfWeek();

            $totalTradeLots = TradeLotSizeVolume::whereBetween(
                    DB::raw('DATE(CONCAT(tlv_year, "-", LPAD(tlv_month, 2, "0"), "-", LPAD(tlv_day, 2, "0")))'),
                    [$startOfWeek, $endOfWeek]
                )->where('tlv_group', 'STANDARD.t')
                ->sum('tlv_lotsize');

            $totalVolume = TradeLotSizeVolume::whereBetween(
                    DB::raw('DATE(CONCAT(tlv_year, "-", LPAD(tlv_month, 2, "0"), "-", LPAD(tlv_day, 2, "0")))'),
                    [$startOfWeek, $endOfWeek]
                )->where('tlv_group', 'STANDARD.t')
                ->sum('tlv_volume_usd');
        } else {
            // Parse the month/year string into a Carbon date
            $carbonDate = Carbon::createFromFormat('m/Y', $monthYear);

            // Get the year and month as integers
            $year = $carbonDate->year;
            $month = $carbonDate->month;
        
            // Check if any record exists where tlv_day = 0
            $hasSummaryRecord = TradeLotSizeVolume::where('tlv_year', $year)
                                                ->where('tlv_month', $month)
                                                ->where('tlv_day', 0)
                                                ->where('tlv_group', 'STANDARD.t')
                                                ->exists();
        
            if ($hasSummaryRecord) {
                // Use the summary record where tlv_day = 0
                $totalTradeLots = TradeLotSizeVolume::where('tlv_year', $year)
                                                    ->where('tlv_month', $month)
                                                    ->where('tlv_day', 0)
                                                    ->where('tlv_group', 'STANDARD.t')
                                                    ->sum('tlv_lotsize');
        
                $totalVolume = TradeLotSizeVolume::where('tlv_year', $year)
                                                ->where('tlv_month', $month)
                                                ->where('tlv_day', 0)
                                                ->where('tlv_group', 'STANDARD.t')
                                                ->sum('tlv_volume_usd');
            } else {
                // No summary record for this month, sum all available days
                $totalTradeLots = TradeLotSizeVolume::where('tlv_year', $year)
                                                    ->where('tlv_month', $month)
                                                    ->where('tlv_group', 'STANDARD.t')
                                                    ->sum('tlv_lotsize');
        
                $totalVolume = TradeLotSizeVolume::where('tlv_year', $year)
                                                ->where('tlv_month', $month)
                                                ->where('tlv_group', 'STANDARD.t')
                                                ->sum('tlv_volume_usd');
            }
        }

   
        // Return the total trade lots and volume as a JSON response
        return response()->json([
            'totalTradeLots' => $totalTradeLots,
            'totalVolume' => $totalVolume,
        ]);
    }

    public function getTradeBrokerPnl(Request $request)
    {
        // Get the selected month (in format "m/Y")
        $monthYear = $request->input('selectedMonth');
        if ($monthYear === 'select_all') {
            $totals = TotalPnlBroker::selectRaw("
                pnl_year, pnl_month,
                SUM(pnl_b_swap) AS total_swap,
                SUM(pnl_b_bookB_markup) AS total_markup,
                SUM(pnl_b_bookB_gross) AS total_gross,
                SUM(pnl_b_total_amt) AS total_broker,
                SUM(trader_totalwin_pricelot) AS total_win_amount,
                SUM(trader_totalwin_trades) AS total_win_deals,
                SUM(trader_totalloss_pricelot) AS total_loss_amount,
                SUM(trader_totalloss_trades) AS total_loss_deals,
            ")
            ->whereNot('pnl_group', 'VIRTUAL')
            ->groupBy('pnl_year', 'pnl_month')
            ->get();
        
            $totalSwap = $totals->sum('total_swap');
            $totalMarkup = $totals->sum('total_markup');
            $totalGross = $totals->sum('total_gross');
            $totalBroker = $totals->sum('total_broker');
            $totalWinDeals = $totals->sum('total_win_deals');
            $totalLossDeals = $totals->sum('total_loss_deals');

            $avgWin = $totalWinDeals > 0 ? $totals->sum('total_win_amount') / $totalWinDeals : 0;
            $avgLoss = $totalLossDeals > 0 ? $totals->sum('total_loss_amount') / $totalLossDeals : 0;

        } elseif (str_starts_with($monthYear, 'last_')) {
            preg_match('/last_(\d+)?_?week?/', $monthYear, $matches);
            $weekNumber = $matches[1] ?? 1;
        
            $startOfWeek = Carbon::now()->subWeeks($weekNumber)->startOfWeek();
            $endOfWeek = Carbon::now()->subWeeks($weekNumber)->endOfWeek();
        
            $totalSwap = TotalPnlBroker::whereBetween(
                    DB::raw('DATE(CONCAT(pnl_year, "-", LPAD(pnl_month, 2, "0"), "-", LPAD(pnl_day, 2, "0")))'),
                    [$startOfWeek, $endOfWeek]
                )->whereNot('pnl_group', 'VIRTUAL')->sum('pnl_b_swap');
        
            $totalMarkup = TotalPnlBroker::whereBetween(
                    DB::raw('DATE(CONCAT(pnl_year, "-", LPAD(pnl_month, 2, "0"), "-", LPAD(pnl_day, 2, "0")))'),
                    [$startOfWeek, $endOfWeek]
                )->whereNot('pnl_group', 'VIRTUAL')->sum('pnl_b_bookB_markup');

            $totalGross = TotalPnlBroker::whereBetween(
                DB::raw('DATE(CONCAT(pnl_year, "-", LPAD(pnl_month, 2, "0"), "-", LPAD(pnl_day, 2, "0")))'),
                [$startOfWeek, $endOfWeek]
            )->whereNot('pnl_group', 'VIRTUAL')->sum('pnl_b_bookB_gross');

            $totalBroker = TotalPnlBroker::whereBetween(
                DB::raw('DATE(CONCAT(pnl_year, "-", LPAD(pnl_month, 2, "0"), "-", LPAD(pnl_day, 2, "0")))'),
                [$startOfWeek, $endOfWeek]
            )->whereNot('pnl_group', 'VIRTUAL')->sum('pnl_b_total_amt');

            $winAmount = TotalPnlBroker::whereBetween(
                DB::raw('DATE(CONCAT(pnl_year, "-", LPAD(pnl_month, 2, "0"), "-", LPAD(pnl_day, 2, "0")))'),
                [$startOfWeek, $endOfWeek]
            )->whereNot('pnl_group', 'VIRTUAL')->sum('trader_totalwin_pricelot');
            
            $winCount = TotalPnlBroker::whereBetween(
                DB::raw('DATE(CONCAT(pnl_year, "-", LPAD(pnl_month, 2, "0"), "-", LPAD(pnl_day, 2, "0")))'),
                [$startOfWeek, $endOfWeek]
            )->whereNot('pnl_group', 'VIRTUAL')->sum('trader_totalwin_trades');

            $lossAmount = TotalPnlBroker::whereBetween(
                DB::raw('DATE(CONCAT(pnl_year, "-", LPAD(pnl_month, 2, "0"), "-", LPAD(pnl_day, 2, "0")))'),
                [$startOfWeek, $endOfWeek]
            )->whereNot('pnl_group', 'VIRTUAL')->sum('trader_totalloss_pricelot');

            $lossCount = TotalPnlBroker::whereBetween(
                DB::raw('DATE(CONCAT(pnl_year, "-", LPAD(pnl_month, 2, "0"), "-", LPAD(pnl_day, 2, "0")))'),
                [$startOfWeek, $endOfWeek]
            )->whereNot('pnl_group', 'VIRTUAL')->sum('trader_totalloss_trades');

            $avgWin = $winCount > 0 ? $winAmount / $winCount : 0;
            $avgLoss = $lossCount > 0 ? $lossAmount / $lossCount : 0;
        } else {
            // Parse the month/year string into a Carbon date
            $carbonDate = Carbon::createFromFormat('m/Y', $monthYear);

            // Get the year and month as integers
            $year = $carbonDate->year;
            $month = $carbonDate->month;
        
            $totalSwap = TotalPnlBroker::where('pnl_year', $year)
                                ->where('pnl_month', $month)
                                ->whereNot('pnl_group', 'VIRTUAL')
                                ->sum('pnl_b_swap');
        
            $totalMarkup = TotalPnlBroker::where('pnl_year', $year)
                            ->where('pnl_month', $month)
                            ->whereNot('pnl_group', 'VIRTUAL')
                            ->sum('pnl_b_bookB_markup');

            $totalGross = TotalPnlBroker::where('pnl_year', $year)
                            ->where('pnl_month', $month)
                            ->whereNot('pnl_group', 'VIRTUAL')
                            ->sum('pnl_b_bookB_gross');

            $totalBroker = TotalPnlBroker::where('pnl_year', $year)
                            ->where('pnl_month', $month)
                            ->whereNot('pnl_group', 'VIRTUAL')
                            ->sum('pnl_b_total_amt');

            $winAmount = TotalPnlBroker::where('pnl_year', $year)
                            ->where('pnl_month', $month)
                            ->whereNot('pnl_group', 'VIRTUAL')
                            ->sum('trader_totalwin_pricelot');

            $winCount = TotalPnlBroker::where('pnl_year', $year)
                            ->where('pnl_month', $month)
                            ->whereNot('pnl_group', 'VIRTUAL')
                            ->sum('trader_totalwin_trades');
                            
            $lossAmount = TotalPnlBroker::where('pnl_year', $year)
                            ->where('pnl_month', $month)
                            ->whereNot('pnl_group', 'VIRTUAL')
                            ->sum('trader_totalloss_pricelot');

            $lossCount = TotalPnlBroker::where('pnl_year', $year)
                            ->where('pnl_month', $month)
                            ->whereNot('pnl_group', 'VIRTUAL')
                            ->sum('trader_totalloss_trades');

            $avgWin = $winCount > 0 ? $winAmount / $winCount : 0;
            $avgLoss = $lossCount > 0 ? $lossAmount / $lossCount : 0;
        }

        // Return the total trade lots and volume as a JSON response
        return response()->json([
            'totalSwap' => $totalSwap,
            'totalMarkup' => $totalMarkup,
            'totalGross' => $totalGross,
            'totalBroker' => $totalBroker,
            'avgWin' => $avgWin,
            'avgLoss' => $avgLoss,
        ]);
    }

    public function getTeams()
    {
        $teams = Team::all()->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'color' => $team->color,
                    'member_count' => $team->team_has_user->count(),
                ];
            });
        
        return response()->json([
            'teams' => $teams,
        ]);
    }
    
    public function getTeamData(Request $request){
        $teamId = $request->input('teamId');
        // Get the selected month (in format "m/Y")
        $monthYear = $request->input('selectedMonth');
        $startDate = null;
        $endDate = null;

        if ($monthYear === 'select_all') {
            // No date filtering for "select_all"
            $startDate = null;
            $endDate = null;
        } elseif (str_starts_with($monthYear, 'last_')) {
            // Calculate the weeks based on the input (last_week, last_2_weeks, etc.)
            preg_match('/last_(\d+)_week/', $monthYear, $matches);
            $weeks = $matches[1] ?? 1;

            // Start from the beginning of the week `x` weeks ago to the end of the last week
            $startDate = Carbon::now()->subWeeks($weeks)->startOfWeek();
            $endDate = Carbon::now()->subWeek($weeks)->endOfWeek();
        } else {
            // Assume month/year format (F Y)
            try {
                // Parse the month/year string into a Carbon date
                $carbonDate = Carbon::createFromFormat('F Y', $monthYear);
                $year = $carbonDate->year;
                $month = $carbonDate->month;

                // Define start and end dates for the selected month
                $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                $endDate = Carbon::create($year, $month, 1)->endOfMonth();
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid date format'], 400);
            }
        }

        $team = Team::with(['team_has_user', 'leader'])->findOrFail($teamId);

        // Get all user ids in the team
        $teamUserIds = TeamHasUser::where('team_id', $team->id)
            ->pluck('user_id')
            ->toArray();

        // Query base for transactions
        $transactionQuery = Transaction::whereIn('user_id', $teamUserIds)
            ->where('status', 'successful');

        // Apply date filtering if needed
        if ($startDate && $endDate) {
            $transactionQuery->whereBetween('approved_at', [$startDate, $endDate]);
        }

        // Calculate total deposit for the team
        $total_deposit = (clone $transactionQuery)
            ->whereIn('transaction_type', ['deposit', 'balance_in', 'rebate_in'])
            ->sum('transaction_amount');

        // Calculate total withdrawal for the team
        $total_withdrawal = (clone $transactionQuery)
            ->whereIn('transaction_type', ['withdrawal', 'balance_out', 'rebate_out'])
            ->sum('transaction_amount');

        // Calculate total adjustment in for the team
        $total_adjustment_in = (clone $transactionQuery)
            ->whereIn('transaction_type', ['balance_in', 'rebate_in'])
            ->sum('transaction_amount');

        // Calculate total adjustment out for the team
        $total_adjustment_out = (clone $transactionQuery)
            ->whereIn('transaction_type', ['balance_out', 'rebate_out'])
            ->sum('transaction_amount');

        // Calculate the net balance for the team
        $transaction_fee_charges = $team->fee_charges > 0 ? $total_deposit / $team->fee_charges : 0;
        $net_balance = $total_deposit - $transaction_fee_charges - $total_withdrawal;

        // Calculate account balance and equity
        $teamIds = AccountType::whereNotNull('account_group_id')
            ->pluck('account_group_id')
            ->toArray();

        $teamBalance = 0;
        $teamEquity = 0;
    
        foreach ($teamIds as $teamId) {
            if (!$startDate || !$endDate) {
                $startDate = Carbon::createFromDate(2020, 1, 1)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
            }
            
            $startDateFormatted = $startDate->format('Y-m-d\TH:i:s.v');
            $endDateFormatted = $endDate->format('Y-m-d\TH:i:s.v');
                
            $response = (new CTraderService)->getMultipleTraders($startDateFormatted, $endDateFormatted, $teamId);

            $accountType = AccountType::where('account_group_id', $teamId)->first();

            $meta_logins = TradingAccount::where('account_type_id', $accountType->id)
                ->whereIn('user_id', $teamUserIds)
                ->pluck('meta_login')
                ->toArray();

            if (isset($response['trader']) && is_array($response['trader'])) {
                foreach ($response['trader'] as $trader) {
                    if (in_array($trader['login'], $meta_logins)) {
                        $moneyDigits = isset($trader['moneyDigits']) ? (int)$trader['moneyDigits'] : 0;
                        $divisor = $moneyDigits > 0 ? pow(10, $moneyDigits) : 1;

                        $teamBalance += $trader['balance'] / $divisor;
                        $teamEquity += $trader['equity'] / $divisor;
                    }
                }
            }
        }
    
        // Return the team data
        return response()->json([
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'fee_charges' => $team->fee_charges,
                'color' => $team->color,
                'leader_name' => $team->leader->first_name,
                'leader_email' => $team->leader->email,
                'profile_photo' => $team->leader->getFirstMediaUrl('profile_photo'),
                'member_count' => $team->team_has_user->count(),
                'deposit' => $total_deposit,
                'withdrawal' => $total_withdrawal,
                'transaction_fee_charges' => $transaction_fee_charges,
                'net_balance' => $net_balance,
                'adjustment_in' => $total_adjustment_in,
                'adjustment_out' => $total_adjustment_out,
                'account_balance' => $teamBalance,
                'account_equity' => $teamEquity,
            ]
        ]);
    }
}
