<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AnnouncementController extends Controller
{
    public function index()
    {
        return Inertia::render('Highlights/Highlights');
    }

    public function getAnnouncement(Request $request)
    {
        $announcements = Announcement::with([
            'media'
        ])
            ->latest()
            ->get()
            ->map(function ($announcement) {
                $announcement->thumbnail = $announcement->getFirstMediaUrl('thumbnail');

                return $announcement;
            });

        return response()->json([
            'announcements' => $announcements,
        ]);
    }

    public function updateAnnouncementStatus(Request $request)
    {
        $announcement = Announcement::findOrFail($request->announcement_id);

        if ($announcement->status === 'inactive') {
            $announcement->status = 'active';
            $announcement->save();

            return back()->with('toast', [
                'title' => trans('public.toast_announcement_has_activated'),
                'type' => 'success',
            ]);
        } elseif ($announcement->status === 'active') {
           
            $announcement->status = 'inactive';
            $announcement->save();

            return back()->with('toast', [
                'title' => trans('public.toast_announcement_has_deactivated'),
                'type' => 'success',
            ]);
        }
    }

    public function createAnnouncement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'visible_to' => ['required'],
            'popup' => ['required'],
            'end_date' => ['after:start_date'],
            'subject' => ['required'],
            'message' => ['required'],
            'thumbnail' => ['required'],
        ])->setAttributeNames([
            'visible_to' => trans('public.visible_to'),
            'popup' => trans('public.popup'),
            'end_date.after' => trans('public.start_end_date'),
            'subject' => trans('public.subject'),
            'message' => trans('public.message'),
            'thumbnail' => trans('public.thumbnail'),
        ]);
        $validator->validate();
        
        // if (!$request->hasFile('reward_thumbnail')) {
        //     throw ValidationException::withMessages([
        //         'reward_thumbnail' => trans('public.at_least_one_field_required'),
        //     ]);
        // }

        try {
            $announcement = Announcement::create([
                'type' => $request->rewards_type,
                'cash_amount' => $request->cash_amount,
                'code' => $request->code,
                'name' => json_encode($request->name),
                'trade_point_required' => $request->trade_point_required,
                // 'start_date' => $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null,
                'expiry_date' => $request->expiry_date ? Carbon::parse($request->expiry_date)->endOfDay() : null,
                'maximum_redemption' => $request->maximum_redemption,
                'max_per_person' => $request->max_per_person,
                'autohide_after_expiry' => $request->autohide_after_expiry,
                'status' => 'inactive',
            ]);

            if ($request->thumbnail) {
                $reward->addMedia($request->thumbnail)->toMediaCollection('thumbnail');
            }

            // Redirect with success message
            return back()->with('toast', [
                'title' => trans("public.toast_create_announcement_success"),
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            // Log the exception and show a generic error message
            Log::error('Error creating the announcement : '.$e->getMessage());

            return back()->with('toast', [
                'title' => 'There was an error creating the announcement.',
                'type' => 'error'
            ]);
        }

    }

    public function editAnnouncement(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'rewards_type' => ['required'],
        //     'cash_amount' => [
        //         Rule::requiredIf($request->rewards_type === 'cash_rewards'),
        //     ],
        //     'code' => ['required', Rule::unique(Reward::class)->ignore($request->reward_id)],
        //     'name' => ['required', 'array'],
        //     'name.*' => ['required', 'string'],
        //     'trade_point_required' => ['required'],
        //     'reward_thumbnail' => ['required'],
        // ])->setAttributeNames([
        //     'rewards_type' => trans('public.rewards_type'),
        //     'cash_amount' => trans('public.cash_amount'),
        //     'code' => trans('public.code'),
        //     'name.*' => trans('public.name'),
        //     'trade_point_required' => trans('public.trade_point_required'),
        //     'reward_thumbnail' => trans('public.reward_thumbnail'),
        // ]);
        // $validator->validate();

        // $reward = Reward::findOrFail($request->reward_id);

        // $reward->update([
        //     'type' => $request->rewards_type,
        //     'cash_amount' => $request->cash_amount,
        //     'code' => $request->code,
        //     'name' => json_encode($request->name),
        //     'trade_point_required' => $request->trade_point_required,
        //     // 'start_date' => $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null,
        //     'expiry_date' => $request->expiry_date ? Carbon::parse($request->expiry_date)->endOfDay() : null,
        //     'maximum_redemption' => $request->maximum_redemption,
        //     'max_per_person' => $request->max_per_person,
        //     'autohide_after_expiry' => $request->autohide_after_expiry,
        // ]);

        // // Handle the profile photo
        // if ($request->hasFile('reward_thumbnail')) {
        //     $reward->clearMediaCollection('reward_thumbnail');
        //     $reward->addMedia($request->reward_thumbnail)->toMediaCollection('reward_thumbnail');
        // } elseif ($request->reward_thumbnail === '') {
        //     // Clear the media if the profile_photo is an empty string
        //     $reward->clearMediaCollection('reward_thumbnail');
        // }

        // // Return a success response
        // return back()->with('toast', [
        //     'title' => trans('public.toast_update_reward_success'),
        //     'type' => 'success',
        // ]);
    }

    public function deleteAnnouncement(Request $request)
    {
    //     $reward = Reward::find($request->reward_id);

    //     try {
    //         $hasProcessingTransactions = Transaction::where('transaction_type', 'redemption')
    //             ->where('status', 'processing')
    //             ->where('category', 'trade_points')
    //             ->whereHas('redemption', function ($query) use ($request) {
    //                 $query->where('reward_id', $request->reward_id);
    //             })
    //             ->exists();

    //         if ($hasProcessingTransactions) {
    //             return back()->with('toast', [
    //                 'title' => trans("public.toast_delete_reward_failed_processing"),
    //                 'type' => 'error',
    //             ]);
    //         }

    //         $reward->delete();

    //         return back()->with('toast', [
    //             'title' => trans("public.toast_delete_reward_success"),
    //             'type' => 'success',
    //         ]);
    //     } catch (\Throwable $e) {
    //         Log::error('Failed to delete reward: ' . $e->getMessage());
    //         return back()->with('toast', [
    //             'title' => 'Failed to delete reward',
    //             'type' => 'error'
    //         ]);
    //     }

    }

}
