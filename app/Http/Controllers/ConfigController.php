<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\SettingAutoApproval;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
}
