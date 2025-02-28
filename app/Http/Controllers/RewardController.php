<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RewardController extends Controller
{
    public function index()
    {
        return Inertia::render('RewardSetting/RewardSetting', [
            // 'accountTypes' => (new GeneralController())->getAccountTypes(true),
        ]);
    }

    public function createReward(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'display_name' => ['required'],
        // ])->setAttributeNames([
        //     'display_name' => trans('public.display_name'),
        // ]);
        // $validator->validate();

        $validator = Validator::make($request->all(), [
            'type' => ['required'],
            'code' => ['required'],
            'name' => ['required'],
            'trade_point_required' => ['required'],
            'attachment' => ['required'],
        ])->setAttributeNames([
            'type' => trans('public.type'),
            'code' => trans('public.code'),
            'name' => trans('public.name'),
            'trade_point_required' => trans('public.trade_point_required'),
            'attachment' => trans('public.attachment'),
        ]);
        $validator->validate();
        
        // if (!$request->hasFile('attachment')) {
        //     throw ValidationException::withMessages([
        //         'attachment' => trans('public.at_least_one_field_required'),
        //     ]);
        // }

        try {
            $reward = Reward::create([
                'type' => $request->type,
                'code' => $request->code,
                'name' => $request->name,
                'trade_point_required' => $request->trade_point_required,
                'start_date' => '',
                'expiry_date' => '',
                'maximum_redemption' => $request->maximum_redemption,
                'autohide_after_expiry' => $request->autohide_after_expiry,
            ]);

            if ($request->attachment) {
                $reward->addMedia($request->attachment)->toMediaCollection('reward_thumbnail');
            }

            // Redirect with success message
            return back()->with('toast', [
                'title' => trans("public.toast_create_reward_success"),
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            // Log the exception and show a generic error message
            Log::error('Error creating the reward : '.$e->getMessage());

            return back()->with('toast', [
                'title' => 'There was an error creating the reward.',
                'type' => 'error'
            ]);
        }

    }

}
