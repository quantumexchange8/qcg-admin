<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\AccountType;
use App\Models\LeaderboardBonus;
use App\Models\SymbolGroup;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Models\TradeRebateSummary;

class TransactionController extends Controller
{
    public function deposit()
    {
        return Inertia::render('Transaction/Deposit', [
            'teams' => (new GeneralController())->getTeams(true),
        ]);
    }

    public function withdrawal()
    {
        return Inertia::render('Transaction/Withdrawal', [
            'teams' => (new GeneralController())->getTeams(true),
        ]);
    }

    public function transfer()
    {
        return Inertia::render('Transaction/Transfer');
    }

    public function bonus()
    {
        return Inertia::render('Transaction/Bonus', [
            'teams' => (new GeneralController())->getTeams(true),
        ]);
    }

    public function rewards()
    {
        return Inertia::render('Transaction/Rewards', [
            'teams' => (new GeneralController())->getTeams(true),
        ]);
    }

    public function rebate()
    {
        return Inertia::render('Transaction/RebatePayout');
    }

    public function incentive()
    {
        return Inertia::render('Transaction/IncentivePayout');
    }

    public function adjustment()
    {
        return Inertia::render('Transaction/AdjustmentHistory');
    }

    public function getTransactionData(Request $request)
    {
        $type = $request->query('type');
        // $selectedTeams = $request->query('selectedTeams'); // Get selectedTeams as a comma-separated string
        $selectedTeam = $request->query('selectedTeam');

        // Convert the comma-separated string to an array
        // $selectedTeamsArray = !empty($selectedTeams) ? explode(',', $selectedTeams) : [];

        $monthYear = $request->input('selectedMonth');

        if ($monthYear === 'select_all') {
            $startDate = Carbon::createFromDate(2020, 1, 1)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        } elseif (str_starts_with($monthYear, 'last_')) {
            preg_match('/last_(\d+)_week/', $monthYear, $matches);
            $weeks = $matches[1] ?? 1;

            $startDate = Carbon::now()->subWeeks($weeks)->startOfWeek();
            $endDate = Carbon::now()->subWeek($weeks)->endOfWeek();
        } else {
            $carbonDate = Carbon::createFromFormat('F Y', $monthYear);

            $startDate = (clone $carbonDate)->startOfMonth()->startOfDay();
            $endDate = (clone $carbonDate)->endOfMonth()->endOfDay();
        }
        // Define common fields
        $commonFields = [
            'id',
            'user_id',
            'category',
            'transaction_type',
            'redemption_id',
            'transaction_number',
            'amount',
            'transaction_charges',
            'transaction_amount',
            'status',
            'remarks',
            'created_at',
            'approved_at',
        ];

        $query = Transaction::with('user.teamHasUser.team', 'from_wallet', 'to_wallet', 'redemption.reward');
        $status = request('status');
        if ($status === 'processing' || $type === 'transfer') {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } else {
            $query->whereBetween('approved_at', [$startDate, $endDate]);
        }

        if ($selectedTeam) {
            $query->whereHas('user.teamHasUser.team', function ($query) use ($selectedTeam) {
                $query->where('id', $selectedTeam);
            });
        }
        // Apply filtering for selected teams
        // if (!empty($selectedTeamsArray)) {
        //     $query->whereHas('user.teamHasUser.team', function ($q) use ($selectedTeamsArray) {
        //         $q->whereIn('id', $selectedTeamsArray);
        //     });
        // }

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
        $data = $query
            ->when($type !== 'transfer', function ($q) {
                $q->orderByRaw('CASE WHEN approved_at IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('approved_at');
            })
            ->orderByDesc('created_at')
            ->get()->map(function ($transaction) use ($commonFields, $type) {
            // Initialize result array with common fields
            $result = $transaction->only($commonFields);

            // Add common user fields
            $result['name'] = $transaction->user ? $transaction->user->first_name : null;
            $result['email'] = $transaction->user ? $transaction->user->email : null;
            $result['role'] = $transaction->user ? $transaction->user->role : null;
            $result['kyc_status'] = $transaction->user ? $transaction->user->kyc_approval : null;

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
            } elseif ($type === 'credit_bonus') {
                $result['team_id'] = $transaction->user->teamHasUser->team_id ?? null;
                $result['team_name'] = $transaction->user->teamHasUser->team->name ?? null;
                $result['team_color'] = $transaction->user->teamHasUser->team->color ?? null;
                $result['to_meta_login'] = $transaction->to_meta_login;
                $result['approved_at'] = $transaction->approved_at;

                $previousCreditBonus = Transaction::where('to_meta_login', $transaction->to_meta_login)
                    ->where('transaction_type', 'credit_bonus')
                    ->where('created_at', '<', $transaction->created_at)
                    ->whereNot('status', 'rejected')
                    ->latest('created_at')
                    ->first();

                // Define the query
                $depositQuery = Transaction::where('to_meta_login', $transaction->to_meta_login)
                    ->whereIn('transaction_type', ['deposit', 'balance_in'])
                    ->where('created_at', '<', $transaction->created_at);

                // Modify query if previous credit_bonus exists
                if ($previousCreditBonus) {
                    $depositQuery->where('created_at', '>', $previousCreditBonus->created_at);
                }

                $result['deposit_amount'] = $depositQuery->sum('transaction_amount');
            } elseif ($type === 'redemption') {
                $reward_name = json_decode($transaction->redemption->reward->name, true);

                $result['team_id'] = $transaction->user->teamHasUser->team_id ?? null;
                $result['team_name'] = $transaction->user->teamHasUser->team->name ?? null;
                $result['team_color'] = $transaction->user->teamHasUser->team->color ?? null;
                $result['from_wallet_id'] = $transaction->from_wallet ? $transaction->from_wallet->id : null;
                $result['approved_at'] = $transaction->approved_at;
                $result['reward_type'] = $transaction->redemption->reward->type;
                $result['reward_code'] = $transaction->redemption->reward->code;
                $result['reward_name'] = $reward_name;
                $result['receiving_account'] = $transaction->redemption->receiving_account ?? null;
                $result['recipient_name'] = $transaction->redemption->recipient_name ?? null;
                $result['phone_number'] = $transaction->redemption->phone_number ?? null;
                $result['address'] = $transaction->redemption->address ?? null;
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
        $monthYear = $request->input('selectedMonth');

        if ($monthYear === 'select_all') {
            $startDate = Carbon::createFromDate(2020, 1, 1)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }  elseif (str_starts_with($monthYear, 'last_')) {
            preg_match('/last_(\d+)_week/', $monthYear, $matches);
            $weeks = $matches[1] ?? 1;

            $startDate = Carbon::now()->subWeeks($weeks)->startOfWeek();
            $endDate = Carbon::now()->subWeek($weeks)->endOfWeek();
        } else {
            $carbonDate = Carbon::createFromFormat('F Y', $monthYear);
            $startDate = (clone $carbonDate)->startOfMonth()->startOfDay();
            $endDate = (clone $carbonDate)->endOfMonth()->endOfDay();
        }

        $allSymbolGroups = SymbolGroup::pluck('display', 'id')->toArray();

        $query = TradeRebateSummary::with('upline_user', 'account_type')
                ->whereBetween('created_at', [$startDate, $endDate]);

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
        $summary = $data->groupBy(fn($item) => Carbon::parse($item['execute_at'])->format('Y-m-d') . '-' . $item['user_id'])
            ->map(function ($group) use ($allSymbolGroups) {
                $group = $group->values(); // Ensure it's a collection and maintain access

                // Generate detailed data for this summary item
                $symbolGroupDetails = $group->groupBy('symbol_group_id')->map(function ($symbolGroupItems) use ($allSymbolGroups) {
                    $symbolGroupId = $symbolGroupItems->first()['symbol_group_id'] ?? null;

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

        $totalAmount = $summary->sum('rebate');

        return response()->json([
            'transactions' => $summary->values(),
            'totalAmount' => $totalAmount,
        ]);

    }

    public function getIncentivePayoutData(Request $request)
    {
        $monthYear = $request->input('selectedMonth');

        if ($monthYear === 'select_all') {
            $startDate = Carbon::createFromDate(2020, 1, 1)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }  elseif (str_starts_with($monthYear, 'last_')) {
            preg_match('/last_(\d+)_week/', $monthYear, $matches);
            $weeks = $matches[1] ?? 1;

            $startDate = Carbon::now()->subWeeks($weeks)->startOfWeek();
            $endDate = Carbon::now()->subWeek($weeks)->endOfWeek();
        } else {
            $carbonDate = Carbon::createFromFormat('F Y', $monthYear);

            $startDate = (clone $carbonDate)->startOfMonth()->startOfDay();
            $endDate = (clone $carbonDate)->endOfMonth()->endOfDay();
        }

        // Start the query for LeaderboardBonus with related models
        $query = LeaderboardBonus::with('leaderboard_profile', 'user')
                ->whereBetween('created_at', [$startDate, $endDate]);

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

    public function getAdjustmentHistoryData(Request $request)
    {
        $monthYear = $request->input('selectedMonth');

        if ($monthYear === 'select_all') {
            $startDate = Carbon::createFromDate(2020, 1, 1)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }  elseif (str_starts_with($monthYear, 'last_')) {
            preg_match('/last_(\d+)_week/', $monthYear, $matches);
            $weeks = $matches[1] ?? 1;

            $startDate = Carbon::now()->subWeeks($weeks)->startOfWeek();
            $endDate = Carbon::now()->subWeek($weeks)->endOfWeek();
        } else {
            $carbonDate = Carbon::createFromFormat('F Y', $monthYear);

            $startDate = (clone $carbonDate)->startOfMonth()->startOfDay();
            $endDate = (clone $carbonDate)->endOfMonth()->endOfDay();
        }

        // Initialize the query
        $query = Transaction::whereIn('transaction_type', [
                'rebate_in', 'rebate_out', 'balance_in', 'balance_out', 'credit_in', 'credit_out',
            ])
            ->where('status', 'successful')
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Fetch transactions
        $adjustment_history = $query->latest()->get();

        $result = [];

        // Transform data: exclude `user` object but retain its relevant fields
        foreach ($adjustment_history as $transaction) {
            $target = null;

            // Check if 'from_wallet_id' or 'to_wallet_id' exist and assign the value
            if ($transaction->from_wallet) {
                $target = $transaction->from_wallet->type;
                unset($transaction->from_wallet);
            } elseif ($transaction->to_wallet) {
                $target = $transaction->to_wallet->type;
                unset($transaction->to_wallet);
            }

            // If 'from_meta_login' or 'to_meta_login' exist, assign directly
            if ($transaction->from_meta_login || $transaction->to_meta_login) {
                $target = $transaction->from_meta_login ?? $transaction->to_meta_login;
            }

            $result[] = array_merge(
                $transaction->toArray(), // Include all transaction data
                [
                    'name' => $transaction->user ? $transaction->user->first_name : null,
                    'email' => $transaction->user ? $transaction->user->email : null,
                    'role' => $transaction->user ? ($transaction->user->role ?? null) : null,
                    'id_number' => $transaction->user ? $transaction->user->id_number : null,
                    'target' => $target,
                    'team_id' => $transaction->user->teamHasUser->team_id ?? null,
                    'team_name' => $transaction->user->teamHasUser->team->name ?? null,
                    'team_color' => $transaction->user->teamHasUser->team->color ?? null,
                ]
            );

            // Remove the nested `user` object from the result
            unset($result[array_key_last($result)]['user']);
        }

        // Calculate the total amount
        $totalAmount = (clone $adjustment_history)
            ->whereNotIn('transaction_type', ['credit_out', 'balance_out'])
            ->sum('transaction_amount');

        // Return response
        return response()->json([
            'transactions' => $result,
            'totalAmount' => $totalAmount,
        ]);
    }

}
