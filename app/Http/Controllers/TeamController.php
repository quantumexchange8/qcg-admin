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
use App\Models\TradingAccount;
use App\Services\CTraderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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

        $startDate = $request->input('startDate') ? Carbon::createFromFormat('Y/m/d', $request->input('startDate'))->startOfDay() : '2024/01/01';
        $endDate = $request->input('endDate') ? Carbon::createFromFormat('Y/m/d', $request->input('endDate'))->endOfDay() : today()->endOfDay();

        $teams = Team::whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->map(function ($team) use ($request, $startDate, $endDate, &$totals) {
                $teamUserIds = TeamHasUser::where('team_id', $team->id)
                    ->pluck('user_id')
                    ->toArray();

                $total_deposit = Transaction::whereIn('user_id', $teamUserIds)
                    ->whereBetween('approved_at', [$startDate, $endDate])
                    ->where(function ($query) {
                        $query->where('transaction_type', 'deposit')
                            ->orWhere('transaction_type', 'balance_in');
                    })
                    ->where('status', 'successful')
                    ->sum('transaction_amount');

                $total_withdrawal = Transaction::whereIn('user_id', $teamUserIds)
                    ->whereBetween('approved_at', [$startDate, $endDate])
                    ->where(function ($query) {
                        $query->where('transaction_type', 'withdrawal')
                            ->orWhere('transaction_type', 'balance_out')
                            ->orWhere('transaction_type', 'rebate_out');
                    })
                    ->where('status', 'successful')
                    ->sum('amount');

                $transaction_fee_charges = $team->fee_charges > 0 ? $total_deposit / $team->fee_charges : 0;
                $net_balance = $total_deposit - $transaction_fee_charges - $total_withdrawal;

                // // Calculate account balance and equity
                // $teamIds = AccountType::whereNotNull('account_group_id')
                //     ->pluck('account_group_id')
                //     ->toArray();

                $teamBalance = 0;
                $teamEquity = 0;

                // foreach ($teamIds as $teamId) {
                //     $startDateFormatted = $startDate->format('Y-m-d\TH:i:s.v');
                //     $endDateFormatted = $endDate->format('Y-m-d\TH:i:s.v');

                //     $response = (new CTraderService)->getMultipleTraders($startDateFormatted, $endDateFormatted, $teamId);

                //     $accountType = AccountType::where('account_group_id', $teamId)->first();

                //     $meta_logins = TradingAccount::where('account_type_id', $accountType->id)->whereIn('user_id', $teamUserIds)->pluck('meta_login')->toArray();

                //     if (isset($response['trader']) && is_array($response['trader'])) {
                //         foreach ($response['trader'] as $trader) {
                //             if (in_array($trader['login'], $meta_logins)) {
                //                 $moneyDigits = isset($trader['moneyDigits']) ? (int)$trader['moneyDigits'] : 0;
                //                 $divisor = $moneyDigits > 0 ? pow(10, $moneyDigits) : 1;

                //                 $teamBalance += $trader['balance'] / $divisor;
                //                 $teamEquity += $trader['equity'] / $divisor;
                //             }
                //         }
                //     }
                // }

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
                    // 'profile_photo' => $team->leader->getFirstMediaUrl('profile_photo'),
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
            'team_name' => ['required', 'string', 'unique:teams,name'],
            'fee_charge' => ['required'],
            'color' => ['required'],
            'agent' => ['required'],
        ])->setAttributeNames([
            'team_name' => trans('public.team_name'),
            'fee_charge' => trans('public.fee_charge'),
            'color' => trans('public.color'),
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

        $team_id = $team->id;
        TeamHasUser::create([
            'team_id' => $team_id,
            'user_id' => $agent_id
        ]);

        $children_ids = User::find($agent_id)->getChildrenIds();
        User::whereIn('id', $children_ids)->chunk(500, function($users) use ($team_id) {
            $users->each->assignedTeam($team_id);
        });

        return back()->with('toast', [
            'title' => trans('public.toast_create_sales_team_success'),
            'type' => 'success',
        ]);
    }

    public function editTeam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_name' => ['required', 'string', 'unique:teams,name,' . $request->team_id],
            'fee_charge' => ['required'],
            'color' => ['required'],
        ])->setAttributeNames([
            'team_name' => trans('public.team_name'),
            'fee_charge' => trans('public.fee_charge'),
            'color' => trans('public.color'),
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

    public function deleteTeam(Request $request)
    {
        Team::destroy($request->id);

        TeamHasUser::where('team_id', $request->id)->delete();

        return back()->with('toast', [
            'title' => trans('public.toast_delete_team_success'),
            'type' => 'success',
        ]);
    }


}
