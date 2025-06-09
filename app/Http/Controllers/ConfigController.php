<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\SettingAutoApproval;
use App\Models\SettingTicketSchedule;
use App\Models\Team;
use App\Models\TradePointHistory;
use App\Models\TradePointDetail;
use App\Models\TradePointPeriod;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ConfigController extends Controller
{
    public function auto_deposit()
    {
        return Inertia::render('Configuration/AutoDeposit');
    }

    public function trade_point_setting()
    {
        return Inertia::render('Configuration/TradePointSetting');
    }

    public function ticket_setting()
    {
        return Inertia::render('Configuration/TicketSetting');
    }

    public function getAutoApprovalSettings()
    {
        $approvalSchedule = SettingAutoApproval::orderBy('day')->get();

        return response()->json([
            'approvalSchedule' => $approvalSchedule,
        ]);
    }

    public function updateAutoApprovalSettings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings.*.start_time' => ['nullable'],
                'settings.*.end_time' => ['nullable'],
                'amount' => ['required'],
            ], [
                'settings.*.end_time.after' => trans('public.start_end_time'),
            ])->setAttributeNames([
                'settings.*.start_time' => trans('public.start_time'),
                'settings.*.end_time' => trans('public.end_time'),
                'amount' => trans('public.max_spread_for_auto_approval'),
            ]);
        
            foreach ($request->settings as $index => $setting) {
                if ($setting['status']) {
                    $validator->sometimes("settings.$index.start_time", ['required'], fn () => true);
                    $validator->sometimes("settings.$index.end_time", ['required', "after:settings.$index.start_time"], fn () => true);
                }
            }
        
            $validator->validate();
        
            foreach ($request->settings as $setting) {
                $data = [
                    'spread_amount' => $request->amount,
                    'status' => $setting['status'] ? 'active' : 'inactive',
                ];
        
                if ($setting['status']) {
                    $data['start_time'] = Carbon::parse($setting['start_time'])->setTimezone('Asia/Kuala_Lumpur')->format('H:i');
                    $data['end_time'] = Carbon::parse($setting['end_time'])->setTimezone('Asia/Kuala_Lumpur')->format('H:i');
                }
        
                SettingAutoApproval::updateOrCreate(
                    ['day' => $setting['day'], 'type' => 'deposit'],
                    $data
                );
            }
        
            return redirect()->back()->with('toast', [
                'title' => trans('public.toast_update_auto_approve_success'),
                'type' => 'success'
            ]);
        } catch (ValidationException $e) {
            throw $e; // Let Laravel handle validation errors for Inertia forms
        } catch (\Throwable $e) {
            Log::error('Auto approval update failed', ['error' => $e->getMessage()]);
        
            return redirect()->back()->with('toast', [
                'title' => trans('public.toast_default_error'),
                'type' => 'error'
            ]);
        }
    }

    public function getTicketScheduleSettings()
    {
        $ticketSchedule = SettingTicketSchedule::orderBy('day')->get();

        return response()->json([
            'ticketSchedule' => $ticketSchedule,
        ]);
    }

    public function updateTicketScheduleSettings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings.*.start_time' => ['nullable'],
                'settings.*.end_time' => ['nullable'],
            ], [
                'settings.*.end_time.after' => trans('public.start_end_time'),
            ])->setAttributeNames([
                'settings.*.start_time' => trans('public.start_time'),
                'settings.*.end_time' => trans('public.end_time'),
            ]);
        
            foreach ($request->settings as $index => $setting) {
                if ($setting['status']) {
                    $validator->sometimes("settings.$index.start_time", ['required'], fn () => true);
                    $validator->sometimes("settings.$index.end_time", ['required', "after:settings.$index.start_time"], fn () => true);
                }
            }
        
            $validator->validate();
        
            foreach ($request->settings as $setting) {
                $data = [
                    'status' => $setting['status'] ? 'active' : 'inactive',
                ];
        
                if ($setting['status']) {
                    $data['start_time'] = Carbon::parse($setting['start_time'])->setTimezone('Asia/Kuala_Lumpur')->format('H:i');
                    $data['end_time'] = Carbon::parse($setting['end_time'])->setTimezone('Asia/Kuala_Lumpur')->format('H:i');
                }
        
                SettingTicketSchedule::updateOrCreate(
                    ['day' => $setting['day']],
                    $data
                );
            }
        
            return redirect()->back()->with('toast', [
                'title' => trans('public.toast_update_ticket_schedule_success'),
                'type' => 'success'
            ]);
        } catch (ValidationException $e) {
            throw $e; // Let Laravel handle validation errors for Inertia forms
        } catch (\Throwable $e) {
            Log::error('Ticket schedule update failed', ['error' => $e->getMessage()]);
        
            return redirect()->back()->with('toast', [
                'title' => trans('public.toast_default_error'),
                'type' => 'error'
            ]);
        }
    }

    public function getVisibleToOptions(Request $request)
    {
        $search = $request->input('search');
    
        // Initialize the query to fetch teams with related users
        $teamsQuery = Team::with(['team_has_user.user:id,first_name,email,id_number']);
    
        // Apply search condition if search term is provided
        if ($search) {
            // Filter teams based on their name and users based on their details
            $teamsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhereHas('team_has_user.user', function ($query) use ($search) {
                          $query->where('first_name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%')
                                ->orWhere('id_number', 'like', '%' . $search . '%');
                      });
            });
        }
    
        // Execute the query to get the teams
        $teams = $teamsQuery->get();
    
        // Initialize an array to hold the transformed data
        $visibleToOptions = [];
    
        // Loop through each team
        foreach ($teams as $team) {
            $members = [];
    
            // Loop through each member in the team and add them if they match the search criteria
            foreach ($team->team_has_user as $teamHasUser) {
                $user = $teamHasUser->user;
    
                // Only add the user if they match the search criteria (no PHP string functions used)
                if ($search) {
                    // Check if any of the user's details match the search
                    if (
                        stripos($user->first_name, $search) !== false ||
                        stripos($user->email, $search) !== false ||
                        stripos($user->id_number, $search) !== false
                    ) {
                        $members[] = [
                            'label' => $user->first_name,
                            'value' => $user->id,
                        ];
                    }
                } else {
                    // If no search term is provided, include all members
                    $members[] = [
                        'label' => $user->first_name,
                        'value' => $user->id,
                    ];
                }
            }
    
            // Only add teams with members to the options array
            if (count($members) > 0) {
                $visibleToOptions[] = [
                    'value' => $team->id,
                    'name' => $team->name,
                    'color' => $team->color,
                    'members' => $members,
                ];
            }
        }
    
        return response()->json([
            'visibleToOptions' => $visibleToOptions,
        ]);
    }

    public function clearPoints(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'select_member' => ['required'],
            'members' => ($request->select_member === 'all_members' ? 'nullable' : 'min:1') . '|array',
            'remarks' => ['required'],
        ])->setAttributeNames([
            'select_member' => trans('public.select_member'),
            'members' => trans('public.visible_to_selected_members'),
            'remarks' => trans('public.remarks'),
        ]);
        $validator->validate();

        if ($request->select_member !== 'all_members') {
            if (!empty($request->members)) {
                foreach ($request->members as $user_id) {
                    $wallet = Wallet::where('type', 'trade_points')
                                    ->where('user_id', $user_id)
                                    ->first();

                    if ($wallet) {
                        $trade_points = $wallet->balance;
                    
                        $wallet->update(['balance' => 0]);
                    
                        TradePointHistory::create([
                            'user_id' => $user_id,
                            'category' => 'reset',
                            'trade_points' => $trade_points,
                            'remarks' => $remarks,
                            'handle_by' => Auth::id(),
                        ]);
                    }

                }
            }
        } else {
            $wallets = Wallet::where('type', 'trade_points')->get();
        
            foreach ($wallets as $wallet) {
                $trade_points = $wallet->balance;
        
                $wallet->update(['balance' => 0]);
        
                TradePointHistory::create([
                    'user_id' => $wallet->user_id,
                    'category' => 'reset',
                    'trade_points' => $trade_points,
                    'remarks' => $remarks,
                    'handle_by' => Auth::id(),
                ]);
            }
        }

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_clear_trade_point_success'),
            'type' => 'success'
        ]);
    }

    public function getTradePointData()
    { 
        $trade_point_details = TradePointDetail::with('symbolGroup:id,display')
            ->whereHas('symbolGroup')
            ->get();

        $overall_period = TradePointPeriod::where('period_name', 'overall')->first();

        $trade_point_periods = TradePointPeriod::whereNot('period_name', 'overall')->get();

        return response()->json([
            'overallPeriod' => $overall_period,
            'tradeDetails' => $trade_point_details,
            'tradePeriods' => $trade_point_periods,
        ]);
    }

    public function updateTradePointRate(Request $request)
    {
        $ids = $request->id;
        $amounts = $request->amount;

        foreach ($ids as $index => $id) {
            TradePointDetail::find($id)->update([
                'trade_point_rate' => $amounts[$index],
            ]);
        }

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_update_trade_point_details_success'),
            'type' => 'success'
        ]);
    }

    public function createTradePeriod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period_name' => ['required','unique:' . TradePointPeriod::class],
            'start_date' => ['required','date'],
            'end_date' => [
                'required','after:start_date'
            ],
        ])->setAttributeNames([
            'period_name' => trans('public.period_name'),
            'start_date' => trans('public.start_date'),
            'end_date' => trans('public.end_date'),
            'end_date.after' => trans('public.start_end_date'),
        ]);
        $validator->validate();

        TradePointPeriod::create([
            'period_name' => $request->period_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'inactive',
        ]);

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_create_trade_point_calculation_success'),
            'type' => 'success'
        ]);
    }

    public function editTradePeriod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period_name' => ['required', Rule::unique(TradePointPeriod::class)->ignore($request->trade_period_id)],
            'start_date' => ['required','date'],
            'end_date' => [
                'required','after:start_date'
            ],
        ])->setAttributeNames([
            'period_name' => trans('public.period_name'),
            'start_date' => trans('public.start_date'),
            'end_date' => trans('public.end_date'),
            'end_date.after' => trans('public.start_end_date'),
        ]);
        $validator->validate();

        $trade_period = TradePointPeriod::findOrFail($request->trade_period_id);

        $trade_period->update([
            'period_name' => $request->period_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'inactive',
        ]);

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_changes_success'),
            'type' => 'success'
        ]);
    }

    public function updatePeriodStatus(Request $request)
    {
        $trade_period = TradePointPeriod::findOrFail($request->trade_period_id);

        // If the account is inactive, immediately activate it and return
        if ($trade_period->status === 'inactive') {
            $trade_period->status = 'active';
            $trade_period->save();

            return back()->with('toast', [
                'title' => trans('public.toast_calculation_period_has_activated'),
                'type' => 'success',
            ]);
        } elseif ($trade_period->status === 'active') {
            // No recent activity -> deactivate account
            $trade_period->status = 'inactive';
            $trade_period->save();

            return back()->with('toast', [
                'title' => trans('public.toast_calculation_period_has_deactivated'),
                'type' => 'success',
            ]);
        }
    }

    public function deleteTradePeriod(Request $request)
    {
        $trade_period = TradePointPeriod::find($request->trade_period_id);

        try {
            $trade_period->delete();

            return back()->with('toast', [
                'title' => trans("public.toast_delete_trade_period_success"),
                'type' => 'success',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to delete trade period: ' . $e->getMessage());
            return back()->with('toast', [
                'title' => 'Failed to delete trade period',
                'type' => 'error'
            ]);
        }

    }

    public function updateCalculationStatus(Request $request)
    {
        $trade_period = TradePointPeriod::findOrFail($request->trade_period_id);

        // If the account is inactive, immediately activate it and return
        if ($trade_period->status === 'inactive') {
            $trade_period->status = 'active';
            $trade_period->save();

            return back()->with('toast', [
                'title' => trans('public.toast_point_calculation_has_enabled'),
                'type' => 'success',
            ]);
        } elseif ($trade_period->status === 'active') {
            // No recent activity -> deactivate account
            $trade_period->status = 'inactive';
            $trade_period->save();

            return back()->with('toast', [
                'title' => trans('public.toast_point_calculation_has_disabled'),
                'type' => 'success',
            ]);
        }
    }
}
