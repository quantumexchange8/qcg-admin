<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\SettingAutoApproval;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ConfigController extends Controller
{
    public function index()
    {
        return Inertia::render('Configuration/Configuration');
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

}
