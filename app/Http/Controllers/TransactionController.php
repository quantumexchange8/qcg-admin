<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\AccountType;
use App\Models\LeaderboardBonus;
use App\Models\SymbolGroup;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TradeRebateSummary;

class TransactionController extends Controller
{
    public function deposit()
    {
        return Inertia::render('Transaction/Deposit');
    }

    public function withdrawal()
    {
        return Inertia::render('Transaction/Withdrawal');
    }

    public function transfer()
    {
        return Inertia::render('Transaction/Transfer');
    }

    public function rebate()
    {
        return Inertia::render('Transaction/RebatePayout');
    }

    public function incentive()
    {
        return Inertia::render('Transaction/IncentivePayout');
    }

    public function getTransactionData(Request $request)
    {
        $type = $request->query('type');
        $selectedMonths = $request->query('selectedMonths'); // Get selectedMonths as a comma-separated string

        // Convert the comma-separated string to an array
        $selectedMonthsArray = !empty($selectedMonths) ? explode(',', $selectedMonths) : [];

        // Define common fields
        $commonFields = [
            'id',
            'user_id',
            'category',
            'transaction_type',
            'transaction_number',
            'amount',
            'transaction_charges',
            'transaction_amount',
            'status',
            'remarks',
            'created_at',
        ];

        if (empty($selectedMonthsArray)) {
            // If selectedMonths is empty, return an empty result
            return response()->json([
                'transactions' => [],
            ]);
        }

        $query = Transaction::with('user.teamHasUser.team', 'from_wallet', 'to_wallet');

        // Apply filtering for each selected month-year pair
        if (!empty($selectedMonthsArray)) {
            $query->where(function ($q) use ($selectedMonthsArray) {
                foreach ($selectedMonthsArray as $range) {
                    [$month, $year] = explode('/', $range);
                    $startDate = "$year-$month-01";
                    $endDate = date("Y-m-t", strtotime($startDate)); // Last day of the month

                    // Add a condition to match transactions for this specific month-year
                    $q->orWhereBetween('created_at', [$startDate, $endDate]);
                }
            });
        }

        // Filter by transaction type
        if ($type) {
            if ($type === 'transfer') {
                $query->where(function ($q) {
                    $q->where('transaction_type', 'transfer_to_account')
                    ->orWhere('transaction_type', 'account_to_account');
                });
            } else {
                $query->where('transaction_type', $type);
            }
        }

        // Fetch data
        $data = $query->latest()->get()->map(function ($transaction) use ($commonFields, $type) {
            // Initialize result array with common fields
            $result = $transaction->only($commonFields);

            // Add common user fields
            $result['name'] = $transaction->user ? $transaction->user->first_name : null;
            $result['email'] = $transaction->user ? $transaction->user->email : null;
            $result['role'] = $transaction->user ? $transaction->user->role : null;

            // Add type-specific fields
            if ($type === 'deposit') {
                $result['team_id'] = $transaction->user->teamHasUser->team_id ?? null;
                $result['team_name'] = $transaction->user->teamHasUser->team->name ?? null;
                $result['team_color'] = $transaction->user->teamHasUser->team->color ?? null;
                $result['from_wallet_address'] = $transaction->from_wallet_address;
                $result['to_wallet_address'] = $transaction->to_wallet_address;
                $result['to_meta_login'] = $transaction->to_meta_login;
                $result['to_wallet_id'] = $transaction->to_wallet ? $transaction->to_wallet->id : null;
                $result['from_wallet_name'] = $transaction->to_wallet ? ($transaction->to_wallet->type === 'rebate_wallet' ? 'rebate' : 'incentive') : null;
            } elseif ($type === 'withdrawal') {
                $result['team_id'] = $transaction->user->teamHasUser->team_id ?? null;
                $result['team_name'] = $transaction->user->teamHasUser->team->name ?? null;
                $result['team_color'] = $transaction->user->teamHasUser->team->color ?? null;
                $result['to_wallet_address'] = $transaction->to_wallet_address;
                $result['from_meta_login'] = $transaction->from_meta_login;
                $result['from_wallet_id'] = $transaction->from_wallet ? $transaction->from_wallet->id : null;
                $result['from_wallet_name'] = $transaction->from_wallet ? ($transaction->from_wallet->type === 'rebate_wallet' ? 'rebate' : 'incentive') : null;
                $result['wallet_id'] = $transaction->payment_account ? $transaction->payment_account->id : null;
                $result['wallet_name'] = $transaction->payment_account ? $transaction->payment_account->payment_account_name : null;
                $result['wallet_address'] = $transaction->payment_account ? $transaction->payment_account->account_no : null;
                $result['approved_at'] = $transaction->approved_at;
            } elseif ($type === 'transfer') {
                $result['from_meta_login'] = $transaction->from_meta_login;
                $result['to_meta_login'] = $transaction->to_meta_login;
                $result['from_wallet_id'] = $transaction->from_wallet ? $transaction->from_wallet->id : null;
                $result['from_wallet_name'] = $transaction->from_wallet ? ($transaction->from_wallet->type === 'rebate_wallet' ? 'rebate' : 'incentive') : null;
                $result['to_wallet_id'] = $transaction->to_wallet ? $transaction->to_wallet->id : null;
                $result['to_wallet_name'] = $transaction->to_wallet ? ($transaction->to_wallet->type === 'rebate_wallet' ? 'rebate' : 'incentive') : null;
            }

            return $result;
        });

        // Filter the data based on the withdrawal source
        if ($request->from) {
            $data = $data->filter(function ($result) use ($request) {
                // Check for account
                if ($request->from === 'account') {
                    return !empty($result['from_meta_login']);
                }
                // Check for rebate
                elseif ($request->from === 'rebate') {
                    return $result['from_wallet_name'] === 'rebate';
                }
                // Check for incentive
                elseif ($request->from === 'incentive') {
                    return $result['from_wallet_name'] === 'incentive';
                }
                return true; // If no specific filter, include all
            });

            $data = $data->values();
        }

        $totalAmount = 0;

        // Recalculate total amount after filtering
        $totalAmount = $data->filter(function ($result) {
            return in_array($result['status'], ['successful']);
        })->sum('transaction_amount');

        return response()->json([
            'transactions' => $data,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function getRebatePayoutData(Request $request)
    {
        // Retrieve query parameters
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        // Fetch all symbol groups from the database
        $allSymbolGroups = SymbolGroup::pluck('display', 'id')->toArray();

        // Initialize query for TradeRebateSummary
        $query = TradeRebateSummary::with('upline_user', 'account_type');

        // Apply date filter based on availability of startDate and/or endDate
        if ($startDate && $endDate) {
            // Both startDate and endDate are provided
            $query->whereDate('execute_at', '>=', $startDate)
                ->whereDate('execute_at', '<=', $endDate);
        } else {
            // Apply default start date if no endDate is provided
            $query->whereDate('execute_at', '>=', '2020-01-01');
        }

        // Fetch and map summarized data from TradeRebateSummary
        $data = $query->get()->map(function ($item) {
            return [
                'user_id' => $item->upline_user_id,
                'name' => $item->upline_user->first_name,
                'email' => $item->upline_user->email,
                'account_type' => $item->account_type->slug ?? null,
                'execute_at' => $item->execute_at,
                'symbol_group_id' => $item->symbol_group_id,
                'volume' => $item->volume,
                'net_rebate' => $item->net_rebate,
                'rebate' => $item->rebate,
            ];
        });

        // Generate summary and details
        $summary = $data->groupBy(function ($item) {
            return $item['execute_at'] . '-' . $item['user_id'];
        })->map(function ($group) use ($allSymbolGroups) {
            $group = collect($group);

            // Generate detailed data for this summary item
            $symbolGroupDetails = $group->groupBy('symbol_group_id')->map(function ($symbolGroupItems) use ($allSymbolGroups) {
                $symbolGroupId = $symbolGroupItems->first()['symbol_group_id'];

                return [
                    'id' => $symbolGroupId,
                    'name' => $allSymbolGroups[$symbolGroupId] ?? 'Unknown',
                    'volume' => $symbolGroupItems->sum('volume'),
                    'net_rebate' => $symbolGroupItems->first()['net_rebate'] ?? 0,
                    'rebate' => $symbolGroupItems->sum('rebate'),
                ];
            })->values();

            // Add missing symbol groups with volume, net_rebate, and rebate as 0
            foreach ($allSymbolGroups as $symbolGroupId => $symbolGroupName) {
                if (!$symbolGroupDetails->pluck('id')->contains($symbolGroupId)) {
                    $symbolGroupDetails->push([
                        'id' => $symbolGroupId,
                        'name' => $symbolGroupName,
                        'volume' => 0,
                        'net_rebate' => 0,
                        'rebate' => 0,
                    ]);
                }
            }

            // Sort the symbol group details array to match the order of symbol groups
            $symbolGroupDetails = $symbolGroupDetails->sortBy('id')->values();

            // Return summary item with details included
            return [
                'user_id' => $group->first()['user_id'],
                'name' => $group->first()['name'],
                'email' => $group->first()['email'],
                'account_type' => $group->first()['account_type'],
                'execute_at' => $group->first()['execute_at'],
                'volume' => $group->sum('volume'),
                'rebate' => $group->sum('rebate'),
                'details' => $symbolGroupDetails,
            ];
        })->values();

        // Sort summary by execute_at in descending order to get the latest dates first
        $summary = $summary->sortByDesc('execute_at');

        $totalAmount = 0;

        $totalAmount = $summary->sum('rebate');

        return response()->json([
            'transactions' => $summary->values(),
            'totalAmount' => $totalAmount,
        ]);
    }

    public function getIncentivePayoutData(Request $request)
    {
        // Get selectedMonths as a comma-separated string
        $selectedMonths = $request->query('selectedMonths');
    
        // Convert the comma-separated string to an array
        $selectedMonthsArray = !empty($selectedMonths) ? explode(',', $selectedMonths) : [];
    
        if (empty($selectedMonthsArray)) {
            // If selectedMonths is empty, return an empty result
            return response()->json([
                'transactions' => [],
                'totalAmount' => 0,
            ]);
        }
    
        // Start the query for LeaderboardBonus with related models
        $query = LeaderboardBonus::with('leaderboard_profile', 'user');
    
        // Apply filtering for each selected month-year pair
        if (!empty($selectedMonthsArray)) {
            $query->where(function ($q) use ($selectedMonthsArray) {
                foreach ($selectedMonthsArray as $range) {
                    [$month, $year] = explode('/', $range);
                    $startDate = "$year-$month-01";
                    $endDate = date("Y-m-t", strtotime($startDate)); // Last day of the month
    
                    // Add a condition to match transactions for this specific month-year
                    $q->orWhereBetween('created_at', [$startDate, $endDate]);
                }
            });
        }
    
        // Fetch and map the data to a cleaner structure
        $data = $query->get()->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'name' => $transaction->user->first_name,
                'email' => $transaction->user->email,
                'mode' => $transaction->leaderboard_profile->sales_calculation_mode === 'personal_sales' ? 'personal' : 'group',
                'sales_category' => $transaction->leaderboard_profile->sales_category,
                'target_amount' => $transaction->target_amount,
                'achieved_amount' => $transaction->achieved_amount,
                'incentive_rate' => $transaction->incentive_rate,
                'incentive_amount' => $transaction->incentive_amount,
                'created_at' => $transaction->created_at,
            ];
        });
    
        // Calculate the total incentive amount
        $totalAmount = $data->sum('incentive_amount');
    
        return response()->json([
            'transactions' => $data,
            'totalAmount' => $totalAmount,
        ]);
    }
}
