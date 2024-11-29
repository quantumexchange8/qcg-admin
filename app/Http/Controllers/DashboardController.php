<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use App\Models\AccountType;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
use App\Services\CTraderService;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard');
    }

    public function getPendingCounts()
    {
        $pendingWithdrawals = Transaction::whereNot('category', 'incentive_wallet')
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'processing')
            ->count();

        $pendingIncentive = Transaction::where('category', 'incentive_wallet')
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'processing')
            ->count();

        return response()->json([
            'pendingWithdrawals' => $pendingWithdrawals,
            'pendingIncentive' => $pendingIncentive,
        ]);
    }

    public function getDashboardData()
    {
        $user = Auth::user();

        $total_deposit = Transaction::where('transaction_type', 'deposit')
            ->where('status', 'successful')
            ->sum('transaction_amount');

        $total_withdrawal = Transaction::where('transaction_type', 'withdrawal')
            ->where('status', 'successful')
            ->sum('amount');

        $total_agent = User::where('role', 'agent')->count();

        $total_member = User::where('role', 'member')->count();

        return response()->json([
            'totalDeposit' => $total_deposit,
            'totalWithdrawal' => $total_withdrawal,
            'totalAgent' => $total_agent,
            'totalMember' => $total_member,
        ]);
    }

    public function getAccountData()
    {
        $from = '2020-01-01T00:00:00.000';
        $to = now()->format('Y-m-d\TH:i:s.v');

        // Ensure account type group IDs are updated before fetching the trader data
        (new CTraderService)->getAccountTypeGroupIds();  // Update account type group IDs
        
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
        $pending_withdrawal = Transaction::where('transaction_type', 'withdrawal')
            ->where('status', 'processing');
    
        $pending_incentive = Transaction::where('transaction_type', 'incentive')
            ->where('status', 'processing');
    
        return response()->json([
            'pendingWithdrawal' => $pending_withdrawal->sum('transaction_amount'),
            'pendingIncentive' => $pending_incentive->sum('transaction_amount'),
            'pendingWithdrawalCount' => $pending_withdrawal->count(),
            'pendingIncentiveCount' => $pending_incentive->count(),
        ]);
    }
}
