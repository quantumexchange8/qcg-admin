<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Team;
use App\Models\User;
use Inertia\Inertia;
use App\Models\AccountType;
use App\Models\TeamHasUser;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TeamSettlement;
use App\Models\TradingAccount;
use App\Services\CTraderService;
use Illuminate\Support\Facades\Auth;
use App\Jobs\TeamMemberAssignmentJob;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    public function index()
    {
        $teamCount = Team::count();

        return Inertia::render('Team/Team', [
            'teamCount' => $teamCount,
        ]);
    }

    public function getTeams(Request $request)
    {
        $totals = [
            'total_net_balance' => 0,
            'total_deposit' => 0,
            'total_withdrawal' => 0,
            'total_charges' => 0,
        ];

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
            $carbonDate = Carbon::parse($monthYear);

            $startDate = (clone $carbonDate)->startOfMonth()->startOfDay();
            $endDate = (clone $carbonDate)->endOfMonth()->endOfDay();
        }
        
        $teams = Team::get()
            ->map(function ($team) use ($request, $startDate, $endDate, &$totals) {
                $teamUserIds = TeamHasUser::where('team_id', $team->id)
                    ->pluck('user_id')
                    ->toArray();
        
                $total_deposit = Transaction::whereIn('user_id', $teamUserIds)
                    ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                        return $query->whereBetween('approved_at', [$startDate, $endDate]);
                    })
                    ->where(function ($query) {
                        $query->where('transaction_type', 'deposit')
                            ->orWhere('transaction_type', 'balance_in')
                            ->orWhere('transaction_type', 'rebate_in');
                    })
                    ->where('status', 'successful')
                    ->sum('transaction_amount');
        
                $total_withdrawal = Transaction::whereIn('user_id', $teamUserIds)
                    ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                        return $query->whereBetween('approved_at', [$startDate, $endDate]);
                    })
                    ->where(function ($query) {
                        $query->where('transaction_type', 'withdrawal')
                            ->orWhere('transaction_type', 'balance_out')
                            ->orWhere('transaction_type', 'rebate_out');
                    })
                    ->where('status', 'successful')
                    ->sum('transaction_amount');
        
                $transaction_fee_charges = $team->fee_charges > 0 ? $total_deposit / $team->fee_charges : 0;
                $net_balance = $total_deposit - $transaction_fee_charges - $total_withdrawal;
        
                // Calculate account balance and equity
                $teamIds = AccountType::whereNotNull('account_group_id')
                    ->pluck('account_group_id')
                    ->toArray();
        
                $teamBalance = 0;
                $teamEquity = 0;
        
                foreach ($teamIds as $teamId) {
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

                // Accumulate the totals
                $totals['total_net_balance'] += $net_balance;
                $totals['total_deposit'] += $total_deposit;
                $totals['total_withdrawal'] += $total_withdrawal;
                $totals['total_charges'] += $transaction_fee_charges;

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
                    'account_balance' => $teamBalance,
                    'account_equity' => $teamEquity,
                ];
            });

        return response()->json([
            'teams' => $teams,
            'total' => $totals,
        ]);
    }
    
    public function createTeam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_name' => ['required', 'string', 'unique:teams,name,NULL,id,deleted_at,NULL'],
            'fee_charge' => ['required'],
            'color' => ['required'],
            'agent' => ['required'],
        ])->setAttributeNames([
            'team_name' => trans('public.team_name'),
            'fee_charge' => trans('public.fee_charge'),
            'color' => trans('public.colour'),
            'agent' => trans('public.agent'),
        ]);
        $validator->validate();

        $agent_id = $request->agent['value'];
        $team = Team::create([
            'name' => $request->team_name,
            'fee_charges' => $request->fee_charge,
            'color' => $request->color,
            'team_leader_id' => $agent_id,
            'edited_by' => Auth::id(),
        ]);

        $user = User::create([
            'first_name' => $request->team_name, //Team Name
            'email' => strtolower(preg_replace('/\s+/', '', $request->team_name)) . "@gmail.com",
            'password' => Hash::make("12345678"),
        ]);

        $user->assignRole("team");

        $team_id = $team->id;
        $teamUser = TeamHasUser::where('user_id', $agent_id)->first();

        if ($teamUser) {
            $teamUser->update([
                'team_id' => $team_id,
            ]);
        } else {
            TeamHasUser::create([
                'team_id' => $team_id,
                'user_id' => $agent_id,
            ]);
        }
        
        $children_ids = User::find($agent_id)->getChildrenIds();
        // Dispatch the job to assign children to the team
        TeamMemberAssignmentJob::dispatch($children_ids, $team_id);

        return back()->with('toast', [
            'title' => trans('public.toast_create_sales_team_success'),
            'type' => 'success',
        ]);
    }

    public function getTeamTransaction(Request $request)
    {
        $teamId = $request->query('team_id');
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
            $carbonDate = Carbon::parse($monthYear);

            $startDate = (clone $carbonDate)->startOfMonth()->startOfDay();
            $endDate = (clone $carbonDate)->endOfMonth()->endOfDay();
        }

        // Get all user IDs associated with the teamId
        $userIds = TeamHasUser::where('team_id', $teamId)
            ->pluck('user_id');

        // Start building the query
        $query = Transaction::with('user')
            ->whereIn('user_id', $userIds)
            ->whereIn('transaction_type', ['deposit', 'balance_in', 'rebate_in', 'withdrawal', 'balance_out', 'rebate_out'])
            ->where('status', 'successful');

        // Apply date range filter if startDate and endDate are provided
        if ($startDate && $endDate) {
            // Both startDate and endDate are provided
            $query->whereDate('approved_at', '>=', $startDate)
                ->whereDate('approved_at', '<=', $endDate);
        } else {
            // Apply default start date if no endDate is provided
            $query->whereDate('approved_at', '>=', '2020-01-01');
        }

        // Execute the query and get the results
        $transactions = $query->latest()->get();

        // Map the results to include user details
        $result = $transactions->map(function ($transaction) {
            return [
                'approved_at' => $transaction->approved_at,
                'user_id' => $transaction->user_id,
                'name' => $transaction->user->first_name,
                'email' => $transaction->user->email,
                // 'profile_photo' => $transaction->user->getFirstMediaUrl('profile_photo'),
                'transaction_type' => $transaction->transaction_type,
                'amount' => $transaction->amount,
                'transaction_charges' => $transaction->transaction_charges,
                'transaction_amount' => $transaction->transaction_amount,
            ];
        });

        // Calculate total values
        $totalAmount = $transactions->sum('amount');
        $totalFee = $transactions->sum('transaction_charges');
        
        // Calculate total values
        $totalDeposit = $transactions->whereIn('transaction_type', ['deposit', 'balance_in', 'rebate_in'])->sum('amount');
        $totalWithdrawals = $transactions->whereIn('transaction_type', ['withdrawal', 'balance_out', 'rebate_out'])->sum('amount');

        // Calculate total balance
        $totalBalance = $totalDeposit - $totalWithdrawals;

        // Return response with totals
        return response()->json([
            'transactions' => $result,
            'totalAmount' => $totalAmount,
            'totalFee' => $totalFee,
            'totalBalance' => $totalBalance,
        ]);
    }

    public function editTeam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_name' => ['required', 'string', 'unique:teams,name,' . $request->team_id . ',id,deleted_at,NULL',],
            'fee_charge' => ['required'],
            'color' => ['required'],
        ])->setAttributeNames([
            'team_name' => trans('public.team_name'),
            'fee_charge' => trans('public.fee_charge'),
            'color' => trans('public.colour'),
        ]);
        $validator->validate();

        $team = Team::findOrFail($request->team_id);

        $team->update([
            'name' => $request->team_name,
            'fee_charges' => $request->fee_charge,
            'color' => $request->color,
        ]);
    
        return back()->with('toast', [
            'title' => trans('public.toast_edit_sales_team_success'),
            'type' => 'success',
        ]);
    }

    public function getAgents()
    {
        $has_team = Team::pluck('team_leader_id');
        $users = User::where('role', 'agent')
            ->whereNotIn('id', $has_team)
            ->get()
            ->map(function ($user) {
                return [
                    'value' => $user->id,
                    'name' => $user->first_name,
                    'email' => $user->email,
                    'total' => count($user->getChildrenIds()),
                ];
            });

        return response()->json([
            'users' => $users,
        ]);
    }

    public function getSettlementReport(Request $request)
    {
        $selectedMonths = $request->query('selectedMonths');
        $selectedTeam = $request->query('selectedTeam');

        $selectedMonthsArray = !empty($selectedMonths) ? explode(',', $selectedMonths) : [];

        $monthYearFilters = array_map(function($monthYear) {
            $date = Carbon::createFromFormat('m/Y', $monthYear);
            return [
                'month' => $date->month,
                'year' => $date->year,
            ];
        }, $selectedMonthsArray);

        $teamSettlements = TeamSettlement::with('team:id,name')
            ->where(function($query) use ($monthYearFilters) {
                if (!empty($monthYearFilters)) {
                    foreach ($monthYearFilters as $filter) {
                        $query->orWhere(function($query) use ($filter) {
                            $query->whereYear('transaction_start_at', $filter['year'])
                                ->whereMonth('transaction_start_at', $filter['month']);
                        });
                    }
                }
            })
            ->when($selectedTeam, function($query) use ($selectedTeam) {
                $query->where('team_id', $selectedTeam);
            })
            ->orderBy('team_deposit', 'desc')
            ->get();
            
        // Initialize an array to hold settlements grouped by month
        $settlementReports = [];

        foreach ($teamSettlements as $settlement) {
            // Format the month for grouping
            $month = $settlement->transaction_start_at->format('Y-m-d');

            // Initialize the month array if it doesn't exist
            if (!isset($settlementReports[$month])) {
                $settlementReports[$month] = [
                    'month' => $month,
                    'total_fee' => 0,
                    'total_balance' => 0,
                    'team_settlements' => []
                ];
            }

            // Add settlement details to the month array
            $settlementReports[$month]['total_fee'] += $settlement->team_fee;
            $settlementReports[$month]['total_balance'] += $settlement->team_balance;

            $settlementReports[$month]['team_settlements'][] = [
                'id' => $settlement->id,
                'team_id' => $settlement->team_id,
                'team_name' => $settlement->team->name ?? null,
                'transaction_start_at' => $settlement->transaction_start_at->format('Y-m-d'),
                'transaction_end_at' => $settlement->transaction_end_at->format('Y-m-d'),
                'team_deposit' => $settlement->team_deposit,
                'team_withdrawal' => $settlement->team_withdrawal,
                'team_fee_percentage' => $settlement->team_fee_percentage,
                'team_fee' => $settlement->team_fee,
                'team_balance' => $settlement->team_balance,
                'settled_at' => $settlement->settled_at->format('Y-m-d'),
            ];
        }

        // Prepare the final response
        return response()->json([
            'settlementReports' => array_values($settlementReports) // Re-index the array to avoid key issues
        ]);
    }

    public function markSettlementReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => ['required'],
            'id' => ['required', 'exists:teams,id'],
        ])->setAttributeNames([
            'month' => trans('public.month'),
            'id' => trans('public.id'),
        ]);
        $validator->validate();

        $team = Team::find($request->id);
        $teamUserIds = TeamHasUser::where('team_id', $request->id)
            ->pluck('user_id')
            ->toArray();

        // Parse the provided month string (e.g., "September 2024") into a Carbon instance
        $month = Carbon::createFromFormat('F Y', $request->month);

        // Get the start date (first day) of the selected month
        $startDate = $month->copy()->startOfMonth();

        // Get the end date (last day) of the selected month
        $endDate = $month->copy()->endOfMonth();

        // Check if a settlement for this team and month has already been processed
        $existingSettlement = TeamSettlement::where('team_id', $request->id)
            ->where('transaction_start_at', $startDate)
            ->where('transaction_end_at', $endDate)
            ->first();

        // If a settlement for this period already exists, return an error
        if ($existingSettlement) {
            return back()->with('toast', [
                'title' => trans('public.toast_settlement_already_exists'),
                'type' => 'warning',
            ]);
        }

        // Calculate total deposits for the team users in the selected month
        $total_deposit = Transaction::whereIn('user_id', $teamUserIds)
            ->whereBetween('approved_at', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where('transaction_type', 'deposit')
                    ->orWhere('transaction_type', 'balance_in')
                    ->orWhere('transaction_type', 'rebate_in');
            })
            ->where('status', 'successful')
            ->sum('transaction_amount');

        // Calculate total withdrawals for the team users in the selected month
        $total_withdrawal = Transaction::whereIn('user_id', $teamUserIds)
            ->whereBetween('approved_at', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where('transaction_type', 'withdrawal')
                    ->orWhere('transaction_type', 'balance_out')
                    ->orWhere('transaction_type', 'rebate_out');
            })
            ->where('status', 'successful')
            ->sum('amount');

        // Calculate fee charges and net balance
        $transaction_fee_charges = $team->fee_charges > 0 ? $total_deposit / $team->fee_charges : 0;
        $net_balance = $total_deposit - $transaction_fee_charges - $total_withdrawal;

        // Create a new settlement record
        TeamSettlement::create([
            'team_id' => $request->id,
            'transaction_start_at' => $startDate,
            'transaction_end_at' => $endDate,
            'team_deposit' => $total_deposit,
            'team_withdrawal' => $total_withdrawal,
            'team_fee_percentage' => $team->fee_charges,
            'team_fee' => $transaction_fee_charges,
            'team_balance' => $net_balance,
            'settled_at' => now(),
        ]);

        return back()->with('toast', [
            'title' => trans('public.toast_mark_settlement_success'),
            'type' => 'success',
        ]);
    }

    public function getTeamSettlementMonth(Request $request)
    {
        // Get the team by ID
        $team = Team::find($request->id);
        if (!$team) {
            return response()->json(['error' => 'Team not found'], 404);
        }
    
        // Get the start date from the team's created_at and the end date as the start of the previous month
        $startDate = Carbon::parse($team->created_at)->startOfMonth();
        $endDate = Carbon::now()->subMonth()->startOfMonth(); // End before the current month
    
        // Generate a list of months from the start date to the end date
        $months = collect();
        $current = $startDate;
        while ($current <= $endDate) {
            $months->push($current->format('F Y'));
            $current->addMonth();
        }
    
        // Get settled months for the team
        $settledMonths = TeamSettlement::where('team_id', $team->id)
            ->pluck('transaction_start_at')
            ->map(function ($date) {
                return Carbon::parse($date)->format('F Y');
            });
    
        // Map months to include marked status
        $result = $months->map(function ($month) use ($settledMonths) {
            return [
                'month' => $month,
                'marked' => $settledMonths->contains($month), // Mark if settlement exists
            ];
        });
    
        return response()->json([
            'months' => $result,
        ]);
    }
        
    public function deleteTeam(Request $request)
    {
        TeamHasUser::where('team_id', $request->id)->update(['team_id' => 1]);

        // Delete the related TeamSettlement records
        TeamSettlement::where('team_id', $request->id)->delete();

        Team::destroy($request->id);

        return back()->with('toast', [
            'title' => trans('public.toast_delete_team_success'),
            'type' => 'success',
        ]);
    }

    public function refreshTeam(Request $request)
    {
        $team = Team::where('id', $request->team_id)->first();

        if ($team) {
            $teamUserIds = TeamHasUser::where('team_id', $team->id)
                ->pluck('user_id')
                ->toArray();

            $monthYear = $request->input('selectedMonth');

            if ($monthYear === 'select_all') {
                $startDate = Carbon::createFromDate(2020, 1, 1)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
            } else {
                $carbonDate = Carbon::parse($monthYear);

                $startDate = (clone $carbonDate)->startOfMonth()->startOfDay();
                $endDate = (clone $carbonDate)->endOfMonth()->endOfDay();
            }
            
            $total_deposit = Transaction::whereIn('user_id', $teamUserIds)
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('approved_at', [$startDate, $endDate]);
                })
                ->where(function ($query) {
                    $query->where('transaction_type', 'deposit')
                        ->orWhere('transaction_type', 'balance_in')
                        ->orWhere('transaction_type', 'rebate_in');
                })
                ->where('status', 'successful')
                ->sum('transaction_amount');

            $total_withdrawal = Transaction::whereIn('user_id', $teamUserIds)
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('approved_at', [$startDate, $endDate]);
                })
                ->where(function ($query) {
                    $query->where('transaction_type', 'withdrawal')
                        ->orWhere('transaction_type', 'balance_out')
                        ->orWhere('transaction_type', 'rebate_out');
                })
                ->where('status', 'successful')
                ->sum('amount');

            $transaction_fee_charges = $team->fee_charges > 0 ? $total_deposit / $team->fee_charges : 0;
            $net_balance = $total_deposit - $transaction_fee_charges - $total_withdrawal;

            // Standard Account and Premium Account group IDs
            $teamIds = AccountType::whereNotNull('account_group_id')
                ->pluck('account_group_id')
                ->toArray();

            $teamBalance = 0;
            $teamEquity = 0;

            foreach ($teamIds as $teamId) {
                // Fetch data for each team ID
                $startDateFormatted = $startDate->format('Y-m-d\TH:i:s.v');
                $endDateFormatted = $endDate->format('Y-m-d\TH:i:s.v');

                $response = (new CTraderService)->getMultipleTraders($startDateFormatted, $endDateFormatted, $teamId);

                // Find the corresponding AccountType model
                $accountType = AccountType::where('account_group_id', $teamId)->first();

                $meta_logins = TradingAccount::where('account_type_id', $accountType->id)
                    ->whereIn('user_id', $teamUserIds)
                    ->pluck('meta_login')
                    ->toArray();

                // Assuming the response is an associative array with a 'trader' key
                if (isset($response['trader']) && is_array($response['trader'])) {
                    foreach ($response['trader'] as $trader) {
                        if (in_array($trader['login'], $meta_logins)) {
                            // Determine the divisor based on moneyDigits
                            $moneyDigits = isset($trader['moneyDigits']) ? (int)$trader['moneyDigits'] : 0;
                            $divisor = $moneyDigits > 0 ? pow(10, $moneyDigits) : 1; // 10^moneyDigits

                            // Adjust balance and equity based on the divisor
                            $teamBalance += $trader['balance'] / $divisor;
                            $teamEquity += $trader['equity'] / $divisor;
                        }
                    }
                }
            }

            $result = [
                'id' => $team->id,
                'name' => $team->name,
                'fee_charges' => $team->fee_charges,
                'color' => $team->color,
                'leader_name' => $team->leader->first_name,
                'leader_email' => $team->leader->email,
                // 'profile_photo' => $team->leader->getFirstMediaUrl('profile_photo'),
                'member_count' => $team->team_has_user->count(),
                'deposit' => $total_deposit,
                'withdrawal' => $total_withdrawal,
                'transaction_fee_charges' => $transaction_fee_charges,
                'net_balance' => $net_balance,
                'account_balance' => $teamBalance,
                'account_equity' => $teamEquity,
            ];

            // Overall totals for all teams
            $overallTotals = Team::get()->reduce(function ($carry, $team) use ($startDate, $endDate) {
                $teamUserIds = TeamHasUser::where('team_id', $team->id)
                    ->pluck('user_id')
                    ->toArray();

                $total_deposit = Transaction::whereIn('user_id', $teamUserIds)
                    ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                        return $query->whereBetween('approved_at', [$startDate, $endDate]);
                    })
                    ->where(function ($query) {
                        $query->where('transaction_type', 'deposit')
                            ->orWhere('transaction_type', 'balance_in')
                            ->orWhere('transaction_type', 'rebate_in');
                    })
                    ->where('status', 'successful')
                    ->sum('transaction_amount');

                $total_withdrawal = Transaction::whereIn('user_id', $teamUserIds)
                    ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                        return $query->whereBetween('approved_at', [$startDate, $endDate]);
                    })
                    ->where(function ($query) {
                        $query->where('transaction_type', 'withdrawal')
                            ->orWhere('transaction_type', 'balance_out')
                            ->orWhere('transaction_type', 'rebate_out');
                    })
                    ->where('status', 'successful')
                    ->sum('amount');

                $transaction_fee_charges = $team->fee_charges > 0 ? $total_deposit / $team->fee_charges : 0;
                $net_balance = $total_deposit - $transaction_fee_charges - $total_withdrawal;

                $carry['total_net_balance'] += $net_balance;
                $carry['total_deposit'] += $total_deposit;
                $carry['total_withdrawal'] += $total_withdrawal;
                $carry['total_charges'] += $transaction_fee_charges;

                return $carry;
            }, [
                'total_net_balance' => 0,
                'total_deposit' => 0,
                'total_withdrawal' => 0,
                'total_charges' => 0,
            ]);
        } else {
            // Handle the case where the team is not found
            $result = null;

            $overallTotals = [
                'total_net_balance' => 0,
                'total_deposit' => 0,
                'total_withdrawal' => 0,
                'total_charges' => 0,
            ];
        }

        return response()->json([
            'refreshed_team' => $result,
            'total' => $overallTotals,
        ]);
    }
}
