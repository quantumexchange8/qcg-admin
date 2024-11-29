<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
use App\Models\LeaderboardBonus;
use App\Models\LeaderboardProfile;
use App\Models\TradeBrokerHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LeaderboardController extends Controller
{
    public function index()
    {
        $profileCount = LeaderboardProfile::count();

        return Inertia::render('Leaderboard/Leaderboard', [
            'profileCount' => $profileCount,
        ]);
    }

    public function getIncentiveProfiles(Request $request)
    {
        $bonusQuery = LeaderboardProfile::query();

        $search = $request->search;
        $sales_calculation_mode = $request->sales_calculation_mode;
        $sales_category = $request->sales_category;

        if (!empty($search)) {
            $bonusQuery->whereHas('user', function ($query) use ($search) {
                $query->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('id_number', 'like', '%' . $search . '%');
            });
        }

        if (!empty($sales_calculation_mode)) {
            $bonusQuery->where('sales_calculation_mode', $sales_calculation_mode);
        }

        if (!empty($sales_category)) {
            $bonusQuery->where('sales_category', $sales_category);
        }

        $totalRecords = $bonusQuery->count();

        $profiles = $bonusQuery->paginate($request->paginate);

        $formattedProfiles = $profiles->map(function($profile) {
            $incentive_amount = 0;
            $achieved_percentage = 0;
            $achieved_amount = 0;

            $today = Carbon::today();

            $useLastPayoutDate = $profile->created_at->eq($profile->last_payout_date);

            // Set start and end dates based on calculation period
            if ($profile->calculation_period == 'every_sunday') {
                // Start of the current week (Monday) and end of the current week (Sunday)
                $startDate = $today->copy()->startOfWeek();
                $endDate = $today->copy()->endOfWeek();
            } elseif ($profile->calculation_period == 'every_second_sunday') {
                // Start of the month
                $startDate = $today->copy()->startOfMonth();

                // Find the first Sunday of the month
                $firstSunday = $startDate->copy()->next('Sunday');

                // Find the second Sunday of the month
                $secondSunday = $firstSunday->copy()->addWeek();

                // If today is before or on the second Sunday, calculate until the day before the second Sunday
                if ($today->lessThan($secondSunday)) {
                    $endDate = $secondSunday->copy()->subDay()->endOfDay();
                } else {
                    // If today is after the second Sunday, set startDate to the second Sunday
                    $startDate = $secondSunday->copy();
                    $endDate = $today->copy()->endOfWeek(); // Or end of current week if needed
                }

            } elseif ($profile->calculation_period == 'first_sunday_of_every_month') {
                $startDate = $today->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();
            } else {
                // Default to the entire current month if no calculation period is specified
                $startDate = $today->copy()->startOfMonth();
                $endDate = $today->copy()->endOfMonth();
            }

            if ($useLastPayoutDate) {
                $startDate = $profile->last_payout_date->copy()->startOfDay();
            }

            if ($profile->sales_calculation_mode == 'personal_sales') {
                if ($profile->sales_category == 'gross_deposit') {
                    $gross_deposit = Transaction::where('user_id', $profile->user_id)
                        ->whereBetween('approved_at', [$startDate, $endDate])
                        ->where(function ($query) {
                            $query->where('transaction_type', 'deposit')
                                ->orWhere('transaction_type', 'balance_in');
                        })
                        ->where('status', 'successful')
                        ->sum('transaction_amount');

                    $achieved_percentage = ($gross_deposit / $profile->target_amount) * 100;
                    $incentive_amount = ($gross_deposit * $profile->incentive_rate) / 100;
                    $achieved_amount = $gross_deposit;
                } elseif ($profile->sales_category == 'net_deposit') {
                    $total_deposit = Transaction::where('user_id', $profile->user_id)
                        ->whereBetween('approved_at', [$startDate, $endDate])
                        ->where(function ($query) {
                            $query->where('transaction_type', 'deposit')
                                ->orWhere('transaction_type', 'balance_in');
                        })
                        ->where('status', 'successful')
                        ->sum('transaction_amount');

                    $total_withdrawal = Transaction::where('user_id', $profile->user_id)
                        ->whereBetween('approved_at', [$startDate, $endDate])
                        ->where(function ($query) {
                            $query->where('transaction_type', 'withdrawal')
                                ->orWhere('transaction_type', 'balance_out')
                                ->orWhere('transaction_type', 'rebate_out');
                        })
                        ->where('status', 'successful')
                        ->sum('transaction_amount');

                    $net_deposit = $total_deposit - $total_withdrawal;

                    $achieved_percentage = ($net_deposit / $profile->target_amount) * 100;
                    $incentive_amount = ($net_deposit * $profile->incentive_rate) / 100;
                    $achieved_amount = $net_deposit;
                } elseif ($profile->sales_category == 'trade_volume') {
                    $meta_logins = $profile->user->tradingAccounts->pluck('meta_login');

                    $trade_volume = TradeBrokerHistory::whereIn('meta_login', $meta_logins)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->sum('trade_lots');

                    $achieved_percentage = ($trade_volume / $profile->target_amount) * 100;
                    $incentive_amount = $achieved_percentage >= $profile->calculation_threshold ? $profile->incentive_rate : 0;
                    $achieved_amount = $trade_volume;
                }
            } elseif ($profile->sales_calculation_mode == 'group_sales') {
                $child_ids = $profile->user->getChildrenIds();
                $child_ids[] = $profile->user_id;

                if ($profile->sales_category == 'gross_deposit') {
                    $gross_deposit = Transaction::whereIn('user_id', $child_ids)
                        ->whereBetween('approved_at', [$startDate, $endDate])
                        ->where(function ($query) {
                            $query->where('transaction_type', 'deposit')
                                ->orWhere('transaction_type', 'balance_in');
                        })
                        ->where('status', 'successful')
                        ->sum('transaction_amount');

                    $achieved_percentage = ($gross_deposit / $profile->target_amount) * 100;
                    $incentive_amount = ($gross_deposit * $profile->incentive_rate) / 100;
                    $achieved_amount = $gross_deposit;
                } elseif ($profile->sales_category == 'net_deposit') {
                    $total_deposit = Transaction::whereIn('user_id', $child_ids)
                        ->whereBetween('approved_at', [$startDate, $endDate])
                        ->where(function ($query) {
                            $query->where('transaction_type', 'deposit')
                                ->orWhere('transaction_type', 'balance_in');
                        })
                        ->where('status', 'successful')
                        ->sum('transaction_amount');

                    $total_withdrawal = Transaction::whereIn('user_id', $child_ids)
                        ->whereBetween('approved_at', [$startDate, $endDate])
                        ->where(function ($query) {
                            $query->where('transaction_type', 'withdrawal')
                                ->orWhere('transaction_type', 'balance_out')
                                ->orWhere('transaction_type', 'rebate_out');
                        })
                        ->where('status', 'successful')
                        ->sum('transaction_amount');

                    $net_deposit = $total_deposit - $total_withdrawal;

                    $achieved_percentage = ($net_deposit / $profile->target_amount) * 100;
                    $incentive_amount = ($net_deposit * $profile->incentive_rate) / 100;
                    $achieved_amount = $net_deposit;
                } elseif ($profile->sales_category == 'trade_volume') {
                    $meta_logins = TradingAccount::whereIn('user_id', $child_ids)
                        ->get()
                        ->pluck('meta_login')
                        ->toArray();

                    $trade_volume = TradeBrokerHistory::whereIn('meta_login', $meta_logins)
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->sum('trade_lots');

                    $achieved_percentage = ($trade_volume / $profile->target_amount) * 100;
                    $incentive_amount = $achieved_percentage >= $profile->calculation_threshold ? $profile->incentive_rate : 0;
                    $achieved_amount = $trade_volume;
                }
            }

            return [
                'id' => $profile->id,
                'user_id' => $profile->user->id,
                'name' => $profile->user->first_name,
                'email' => $profile->user->email,
                'sales_calculation_mode' => $profile->sales_calculation_mode == 'personal_sales' ? 'personal' : 'group',
                'sales_category' => $profile->sales_category,
                'target_amount' => $profile->target_amount,
                'incentive_amount' => $incentive_amount,
                'incentive_rate' => $profile->incentive_rate,
                'calculation_threshold' => intval($profile->calculation_threshold),
                'achieved_percentage' => $achieved_percentage,
                'achieved_amount' => $achieved_amount,
                'calculation_period' => $profile->calculation_period,
                'last_payout_date' => $profile->last_payout_date,
                'next_payout_date' => $profile->next_payout_date,
            ];
        });

        // Sort the formatted profiles based on the category provided in the request
        $sortCategory = $request->category; // This should be either "incentive_amount" or "achieved_percentage"
        if (in_array($sortCategory, ['incentive_amount', 'achieved_percentage'])) {
            $formattedProfiles = $formattedProfiles->sortByDesc($sortCategory);
        }

        return response()->json([
            'incentiveProfiles' => $formattedProfiles->values(),
            'totalRecords' => $totalRecords,
            'currentPage' => $profiles->currentPage(),
        ]);
    }

    public function createIncentiveProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agent' => ['required'],
            'sales_calculation_mode' => ['required'],
            'sales_category' => ['required'],
            'target_amount' => ['required'],
            'incentive' => ['required'],
            'calculation_threshold' => ['required'],
            'calculation_period' => ['required'],
        ])->setAttributeNames([
            'agent' => trans('public.agent'),
            'sales_calculation_mode' => trans('public.sales_calculation_mode'),
            'sales_category' => trans('public.sales_category'),
            'target_amount' => trans('public.set_target_amount'),
            'incentive' => trans('public.incentive_rate'),
            'calculation_threshold' => trans('public.calculation_threshold'),
            'calculation_period' => trans('public.calculation_period'),
        ]);
        $validator->validate();

        $leaderboard_profile = LeaderboardProfile::create([
            'user_id' => $request->agent['value'],
            'sales_calculation_mode' => $request->sales_calculation_mode,
            'sales_category' => $request->sales_category,
            'target_amount' => $request->target_amount,
            'incentive_rate' => $request->incentive,
            'calculation_threshold' => $request->calculation_threshold,
            'calculation_period' => $request->calculation_period,
            'last_payout_date' => Carbon::now(),
            'edited_by' => Auth::id()
        ]);

        switch ($leaderboard_profile->calculation_period) {
            case 'every_sunday':
                $nextPayout = Carbon::now()->next(Carbon::SUNDAY)->startOfDay();
                $leaderboard_profile->update([
                    'next_payout_date' => $nextPayout
                ]);
                break;

            case 'every_second_sunday':
                $nextPayout = Carbon::now()->next(Carbon::SUNDAY)->addWeek()->startOfDay();
                $leaderboard_profile->update([
                    'next_payout_date' => $nextPayout
                ]);
                break;

            case 'first_sunday_of_every_month':
                $nextPayout = Carbon::now()->startOfMonth()->addMonth()->firstOfMonth(Carbon::SUNDAY)->startOfDay();
                $leaderboard_profile->update([
                    'next_payout_date' => $nextPayout
                ]);
                break;

            default:
                return response()->json(['error' => 'Invalid period'], 400);
        }

        $user = User::find($leaderboard_profile->user_id);

        if (empty($user->incentive_wallet)) {
            Wallet::create([
                'user_id' => $user->id,
                'type' => 'incentive_wallet',
                'address' => str_replace('AID', 'BW', $user->id_number),
                'balance' => 0
            ]);
        }

        return redirect()->back()->with('toast', value: [
            "title" => trans('public.toast_create_incentive_profile_success'),
            "type" => "success"
        ]);
    }

    public function getAgents()
    {
        $users = User::where('role', 'agent')
            ->get()
            ->map(function ($user) {
                return [
                    'value' => $user->id,
                    'name' => $user->first_name,
                ];
            });

        return response()->json([
            'users' => $users,
        ]);
    }

    public function editIncentiveProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_amount' => ['required'],
            'incentive' => ['required'],
            'calculation_threshold' => ['required'],
            'calculation_period' => ['required'],
        ])->setAttributeNames([
            'target_amount' => trans('public.set_target_amount'),
            'incentive' => trans('public.incentive_rate'),
            'calculation_threshold' => trans('public.calculation_threshold'),
            'calculation_period' => trans('public.calculation_period'),
        ]);
        $validator->validate();

        $profile = LeaderboardProfile::find($request->profile_id);

        $profile->update([
            'target_amount' => $request->target_amount,
            'incentive_rate' => $request->incentive,
            'calculation_threshold' => $request->calculation_threshold,
            'edited_by' => Auth::id()
        ]);

        if ($profile->calculation_period != $request->calculation_period) {
            switch ($request->calculation_period) {
                case 'every_sunday':
                    $nextPayout = Carbon::now()->next(Carbon::SUNDAY)->startOfDay();
                    $profile->update([
                        'calculation_period' => $request->calculation_period,
                        'next_payout_date' => $nextPayout
                    ]);
                    break;

                case 'every_second_sunday':
                    $nextPayout = Carbon::now()->next(Carbon::SUNDAY)->addWeek()->startOfDay();
                    $profile->update([
                        'calculation_period' => $request->calculation_period,
                        'next_payout_date' => $nextPayout
                    ]);
                    break;

                case 'first_sunday_of_every_month':
                    $nextPayout = Carbon::now()->startOfMonth()->addMonth()->firstOfMonth(Carbon::SUNDAY)->startOfDay();
                    $profile->update([
                        'calculation_period' => $request->calculation_period,
                        'next_payout_date' => $nextPayout
                    ]);
                    break;

                default:
                    return response()->json(['error' => 'Invalid period'], 400);
            }
        }

        return redirect()->back()->with('toast', [
            "title" => trans('public.toast_edit_incentive_profile_success'),
            "type" => "success"
        ]);
    }

    public function getStatementData(Request $request)
    {
        $bonusQuery = LeaderboardBonus::where('leaderboard_profile_id', $request->profile_id);

        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        if ($startDate && $endDate) {
            $start_date = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
            $end_date = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

            $bonusQuery->whereBetween('created_at', [$start_date, $end_date]);
        }

        $bonuses = $bonusQuery
            ->get()
            ->map(function ($bonus) {
                return [
                    'id' => $bonus->id,
                    'target_amount' => $bonus->target_amount,
                    'achieved_amount' => $bonus->achieved_amount,
                    'incentive_rate' => $bonus->incentive_rate,
                    'incentive_amount' => $bonus->incentive_amount,
                    'created_at' => $bonus->created_at,
                ];
            });

        return response()->json([
            'bonuses' => $bonuses,
            'totalBonusAmount' => $bonusQuery->sum('incentive_amount'),
        ]);        
    }

    public function deleteIncentiveProfile(Request $request)
    {
        $profile = LeaderboardProfile::find($request->id);

        if ($profile) {
            $profile->leaderboard_bonus()->delete();
            $profile->delete();

            return redirect()->back()->with('toast', [
                "title" => trans('public.toast_delete_incentive_profile_success'),
                "type" => "success"
            ]);
        }
    }
}
