<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\Models\Reward;
use App\Models\Transaction;

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
        $validator = Validator::make($request->all(), [
            'rewards_type' => ['required'],
            'cash_amount' => [
                Rule::requiredIf($request->rewards_type === 'cash_rewards'),
            ],
            'code' => ['required', Rule::unique(Reward::class)],
            'name' => ['required', 'array'],
            'name.*' => ['required', 'string'],
            'trade_point_required' => ['required'],
            'reward_thumbnail' => ['required'],
        ])->setAttributeNames([
            'rewards_type' => trans('public.rewards_type'),
            'cash_amount' => trans('public.cash_amount'),
            'code' => trans('public.code'),
            'name.*' => trans('public.name'),
            'trade_point_required' => trans('public.trade_point_required'),
            'reward_thumbnail' => trans('public.reward_thumbnail'),
        ]);
        $validator->validate();
        
        // if (!$request->hasFile('reward_thumbnail')) {
        //     throw ValidationException::withMessages([
        //         'reward_thumbnail' => trans('public.at_least_one_field_required'),
        //     ]);
        // }

        try {
            $reward = Reward::create([
                'type' => $request->rewards_type,
                'cash_amount' => $request->cash_amount,
                'code' => $request->code,
                'name' => json_encode($request->name),
                'trade_point_required' => $request->trade_point_required,
                // 'start_date' => $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null,
                'expiry_date' => $request->expiry_date ? Carbon::createFromFormat('Y-m-d', $request->expiry_date)->endOfDay() : null,
                'maximum_redemption' => $request->maximum_redemption,
                'max_per_person' => $request->max_per_person,
                'autohide_after_expiry' => $request->autohide_after_expiry,
                'status' => 'inactive',
            ]);

            if ($request->reward_thumbnail) {
                $reward->addMedia($request->reward_thumbnail)->toMediaCollection('reward_thumbnail');
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

    public function getRewardData(Request $request)
    {
        $query = Reward::withCount(['redemption as redemption_count' => function ($query) {
            $query->whereHas('transaction', function ($q) {
                $q->where('status', '!=', 'rejected');
            });
        }]);

        if ($request->filter == 'most_redeemed') {
            $query->orderByDesc('redemption_count');
        } elseif ($request->filter == 'cash_rewards_only') {
            $query->where('type', 'cash_rewards');
        } elseif ($request->filter == 'physical_rewards_only') {
            $query->where('type', 'physical_rewards');
        } else {
            $query->orderBy('trade_point_required');
        }

        $rewards = $query->get()
            ->map(function ($reward) {
                $name = json_decode($reward->name, true);
                $reward_thumbnail = $reward->getFirstMediaUrl('reward_thumbnail');

                return [
                    'reward_id' => $reward->id,
                    'type' => $reward->type,
                    'cash_amount' => (float) $reward->cash_amount,
                    'code' => $reward->code,
                    'trade_point_required' => $reward->trade_point_required,
                    // 'start_date' => $reward->start_date,
                    'expiry_date' => $reward->expiry_date,
                    'maximum_redemption' => $reward->maximum_redemption,
                    'max_per_person' => $reward->max_per_person,
                    'autohide_after_expiry' => $reward->autohide_after_expiry,
                    'status' => $reward->status,
                    'name' => $name,
                    'reward_thumbnail' => $reward_thumbnail,
                    'redeem_count' => $reward->redemption_count,
                ];
            })
            ->values(); 

        return response()->json([
            'rewards' => $rewards,
        ]);
    }

    public function deleteReward(Request $request)
    {
        $reward = Reward::find($request->reward_id);

        try {
            $hasProcessingTransactions = Transaction::where('transaction_type', 'redemption')
                ->where('status', 'processing')
                ->where('category', 'trade_points')
                ->whereHas('redemption', function ($query) use ($request) {
                    $query->where('reward_id', $request->reward_id);
                })
                ->exists();

            if ($hasProcessingTransactions) {
                return back()->with('toast', [
                    'title' => trans("public.toast_delete_reward_failed_processing"),
                    'type' => 'error',
                ]);
            }

            $reward->delete();

            return back()->with('toast', [
                'title' => trans("public.toast_delete_reward_success"),
                'type' => 'success',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to delete reward: ' . $e->getMessage());
            return back()->with('toast', [
                'title' => 'Failed to delete reward',
                'type' => 'error'
            ]);
        }

    }

    public function editReward(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rewards_type' => ['required'],
            'cash_amount' => [
                Rule::requiredIf($request->rewards_type === 'cash_rewards'),
            ],
            'code' => ['required', Rule::unique(Reward::class)->ignore($request->reward_id)],
            'name' => ['required', 'array'],
            'name.*' => ['required', 'string'],
            'trade_point_required' => ['required'],
            'reward_thumbnail' => ['required'],
        ])->setAttributeNames([
            'rewards_type' => trans('public.rewards_type'),
            'cash_amount' => trans('public.cash_amount'),
            'code' => trans('public.code'),
            'name.*' => trans('public.name'),
            'trade_point_required' => trans('public.trade_point_required'),
            'reward_thumbnail' => trans('public.reward_thumbnail'),
        ]);
        $validator->validate();

        $reward = Reward::findOrFail($request->reward_id);

        $reward->update([
            'type' => $request->rewards_type,
            'cash_amount' => $request->cash_amount,
            'code' => $request->code,
            'name' => json_encode($request->name),
            'trade_point_required' => $request->trade_point_required,
            // 'start_date' => $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null,
            'expiry_date' => $request->expiry_date ? Carbon::createFromFormat('Y-m-d', $request->expiry_date)->endOfDay() : null,
            'maximum_redemption' => $request->maximum_redemption,
            'max_per_person' => $request->max_per_person,
            'autohide_after_expiry' => $request->autohide_after_expiry,
        ]);

        // Handle the profile photo
        if ($request->hasFile('reward_thumbnail')) {
            $reward->clearMediaCollection('reward_thumbnail');
            $reward->addMedia($request->reward_thumbnail)->toMediaCollection('reward_thumbnail');
        } elseif ($request->reward_thumbnail === '') {
            // Clear the media if the profile_photo is an empty string
            $reward->clearMediaCollection('reward_thumbnail');
        }

        // Return a success response
        return back()->with('toast', [
            'title' => trans('public.toast_update_reward_success'),
            'type' => 'success',
        ]);
    }

    public function updateRewardStatus(Request $request)
    {
        $reward = Reward::findOrFail($request->reward_id);

        // If the account is inactive, immediately activate it and return
        if ($reward->status === 'inactive') {
            $reward->status = 'active';
            $reward->save();

            return back()->with('toast', [
                'title' => trans('public.toast_rewards_has_activated'),
                'type' => 'success',
            ]);
        } elseif ($reward->status === 'active') {
            // No recent activity -> deactivate account
            $reward->status = 'inactive';
            $reward->save();

            return back()->with('toast', [
                'title' => trans('public.toast_rewards_has_deactivated'),
                'type' => 'success',
            ]);
        }
    }
}
