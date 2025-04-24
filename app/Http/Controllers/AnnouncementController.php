<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Announcement;
use App\Models\UserAnnouncementVisibility;
use App\Models\Team;
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
            'members' => ($request->visible_to === 'public' ? 'nullable' : 'min:1') . '|array',
            'popup' => ['required'],
            'end_date' => ['after:start_date'],
            'subject' => ['required'],
            'message' => ['required'],
            'thumbnail' => ['required'],
        ])->setAttributeNames([
            'visible_to' => trans('public.visible_to'),
            'members' => trans('public.visible_to_selected_members'),
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
                'title' => $request->subject,
                'content' => $request->message,
                'start_date' => $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null,
                'end_date' => $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : null,
                'recipient' => $request->visible_to,
                'status' => 'inactive',
                'popup' => $request->popup === 'none' ? false : true,
                'popup_login' => $request->popup === 'none' ? null : $request->popup,
            ]);

            if ($request->thumbnail) {
                $reward->addMedia($request->thumbnail)->toMediaCollection('thumbnail');
            }

            // If the visible_to is not 'public', handle user visibility assignment
            if ($request->visible_to !== 'public') {
                // Add the new users for this account type
                if (!empty($request->members)) {
                    foreach ($request->members as $user_id) {
                        UserAnnouncementVisibility::create([
                            'announcement_id' => $announcement->id,
                            'user_id' => $user_id,
                        ]);
                    }
                }
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
