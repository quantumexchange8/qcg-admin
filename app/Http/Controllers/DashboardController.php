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
use App\Jobs\CashWalletTransferJob;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', [
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


        return response()->json([
            'pendingWithdrawals' => $pendingWithdrawals,
            'pendingBonus' => $pendingBonus,
            'pendingIncentive' => $pendingIncentive,
        ]);
    }

    public function getDashboardData()
    {
        $total_deposit = Transaction::whereIn('transaction_type', ['deposit', 'rebate_in', 'balance_in', 'credit_in'])
            ->where('status', 'successful')
            ->sum('transaction_amount');
    
        $total_withdrawal = Transaction::whereIn('transaction_type', ['withdrawal', 'rebate_out', 'balance_out', 'credit_out'])
            ->where('status', 'successful')
            ->sum('amount');
        
        $total_agent = User::where('role', 'agent')->count();

        $total_member = User::where('role', 'member')->count();

        $today_deposit = Transaction::whereIn('transaction_type', ['deposit', 'rebate_in', 'balance_in', 'credit_in'])
            ->where('status', 'successful')
            ->whereDate('created_at', today())
            ->sum('transaction_amount');

        $today_withdrawal = Transaction::whereIn('transaction_type', ['withdrawal', 'rebate_out', 'balance_out', 'credit_out'])
            ->where('status', 'successful')
            ->whereDate('created_at', today())
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
            $accountType = AccountType::where('account_group_id', $groupId)->first();

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
        $totalBalance = AccountType::sum('account_group_balance');
        $totalEquity = AccountType::sum('account_group_equity');

        // Return the total balance and total equity as a JSON response
        return response()->json([
            'totalBalance' => $totalBalance,
            'totalEquity' => $totalEquity,
        ]);
    }

    public function getPendingData()
    {
        $pending_withdrawal = Transaction::whereNotIn('category', ['incentive_wallet', 'bonus_wallet'])
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'processing');

        $pending_bonus = Transaction::where('category', 'bonus_wallet')
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'processing');

        $pending_incentive = Transaction::where('category', 'incentive_wallet')
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'processing');

        return response()->json([
            'pendingWithdrawal' => $pending_withdrawal->sum('transaction_amount'),
            'pendingBonus' => $pending_bonus->sum('transaction_amount'),
            'pendingIncentive' => $pending_incentive->sum('transaction_amount'),
            'pendingWithdrawalCount' => $pending_withdrawal->count(),
            'pendingBonusCount' => $pending_bonus->count(),
            'pendingIncentiveCount' => $pending_incentive->count(),
        ]);
    }

    public function getTradeLotVolume(Request $request)
    {
        // Get the selected month (in format "m/Y")
        $monthYear = $request->input('selectedMonth');
    
        // Parse the month/year string into a Carbon date
        $carbonDate = Carbon::createFromFormat('m/Y', $monthYear);
    
        // Get the year and month as integers
        $year = $carbonDate->year;
        $month = $carbonDate->month;
    
        // Check if any record exists where tlv_day = 0
        $hasSummaryRecord = TradeLotSizeVolume::where('tlv_year', $year)
                                              ->where('tlv_month', $month)
                                              ->where('tlv_day', 0)
                                              ->exists();
    
        if ($hasSummaryRecord) {
            // Use the summary record where tlv_day = 0
            $totalTradeLots = TradeLotSizeVolume::where('tlv_year', $year)
                                                ->where('tlv_month', $month)
                                                ->where('tlv_day', 0)
                                                ->sum('tlv_lotsize');
    
            $totalVolume = TradeLotSizeVolume::where('tlv_year', $year)
                                             ->where('tlv_month', $month)
                                             ->where('tlv_day', 0)
                                             ->sum('tlv_volume_usd');
        } else {
            // No summary record for this month, sum all available days
            $totalTradeLots = TradeLotSizeVolume::where('tlv_year', $year)
                                                ->where('tlv_month', $month)
                                                ->sum('tlv_lotsize');
    
            $totalVolume = TradeLotSizeVolume::where('tlv_year', $year)
                                             ->where('tlv_month', $month)
                                             ->sum('tlv_volume_usd');
        }
    
        // Return the total trade lots and volume as a JSON response
        return response()->json([
            'totalTradeLots' => $totalTradeLots,
            'totalVolume' => $totalVolume,
        ]);
    }
        
    public function getTeamsData(Request $request)
    {
        // Get the selected month (in format "m/Y")
        $monthYear = $request->input('selectedMonth');
        
        // Parse the month/year string into a Carbon date
        $carbonDate = Carbon::createFromFormat('F Y', $monthYear);
        
        // Get the year and month as integers
        $year = $carbonDate->year;
        $month = $carbonDate->month;
        
        // Retrieve all teams and their related data
        $teams = Team::all()->map(function ($team) use ($year, $month) {
            // Get all user ids in the team
            $teamUserIds = TeamHasUser::where('team_id', $team->id)
                ->pluck('user_id')
                ->toArray();
    
            // Calculate total deposit for the team (filtered by month and year)
            $total_deposit = Transaction::whereIn('user_id', $teamUserIds)
                ->whereYear('approved_at', $year)
                ->whereMonth('approved_at', $month)
                ->whereIn('transaction_type', ['deposit', 'balance_in', 'rebate_in'])
                ->where('status', 'successful')
                ->sum('transaction_amount');
    
            // Calculate total withdrawal for the team (filtered by month and year)
            $total_withdrawal = Transaction::whereIn('user_id', $teamUserIds)
                ->whereYear('approved_at', $year)
                ->whereMonth('approved_at', $month)
                ->whereIn('transaction_type', ['withdrawal', 'balance_out', 'rebate_out'])
                ->where('status', 'successful')
                ->sum('transaction_amount');
    
            // Calculate total adjustment in for the team (filtered by month and year)
            $total_adjustment_in = Transaction::whereIn('user_id', $teamUserIds)
                ->whereYear('approved_at', $year)
                ->whereMonth('approved_at', $month)
                ->whereIn('transaction_type', ['balance_in', 'rebate_in'])
                ->where('status', 'successful')
                ->sum('transaction_amount');
        
            // Calculate total adjustment out for the team (filtered by month and year)
            $total_adjustment_out = Transaction::whereIn('user_id', $teamUserIds)
                ->whereYear('approved_at', $year)
                ->whereMonth('approved_at', $month)
                ->whereIn('transaction_type', ['balance_out', 'rebate_out'])
                ->where('status', 'successful')
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
                $startDateFormatted = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d\TH:i:s.v');
                $endDateFormatted = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d\TH:i:s.v');
                    
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
            return [
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
            ];
        });
    
        return response()->json([
            'teams' => $teams,
        ]);
    }
    
}
