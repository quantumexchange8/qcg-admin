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
use Illuminate\Validation\Rule;

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

    public function togglePinStatus(Request $request, Announcement $announcement)
    {
        // Validate the request if needed
        $request->validate([
            'pinned' => 'required|boolean',
        ]);

        // Update the pinned status in the database
        $announcement->pinned = $request->input('pinned');
        $announcement->save();

        return response()->json([
            'message' => $announcement->pinned ? 'Pinned successfully' : 'Unpinned successfully',
            'announcement' => $announcement
        ]);
    }

    public function createAnnouncement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'visible_to' => ['required'],
            'members' => ($request->visible_to === 'public' ? 'nullable' : 'min:1') . '|array',
            'popup' => ['required'],
            'start_date' => ['nullable', 'date'],
            'end_date' => [
                'nullable',
                $request->start_date ? 'after:start_date' : null,
            ],
            'subject' => ['required'],
            'message' => [
                Rule::requiredIf(function () use ($request) {
                    return $request->status !== 'draft';
                }),
            ],
            'thumbnail' => [
                Rule::requiredIf(function () use ($request) {
                    return $request->status !== 'draft';
                }),
            ],
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

        try {
            $announcement = Announcement::create([
                'title' => $request->subject,
                'content' => $request->message ? $request->message : null,
                'start_date' => $request->start_date ? $request->start_date : null,
                'end_date' => $request->end_date ? $request->end_date : null,
                'recipient' => $request->visible_to,
                'status' => $request->status,
                'popup' => $request->popup === 'none' ? false : true,
                'popup_login' => $request->popup === 'none' ? null : $request->popup,
            ]);

            if ($request->thumbnail) {
                $announcement->addMedia($request->thumbnail)->toMediaCollection('thumbnail');
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
        $validator = Validator::make($request->all(), [
            'visible_to' => ['required'],
            'members' => ($request->visible_to === 'public' ? 'nullable' : 'min:1') . '|array',
            'popup' => ['required'],
            'start_date' => ['nullable', 'date'],
            'end_date' => [
                'nullable',
                $request->start_date ? 'after:start_date' : null,
            ],
            'subject' => ['required'],
            'message' => [
                Rule::requiredIf(function () use ($request) {
                    return $request->status !== 'draft';
                }),
            ],
            'thumbnail' => [
                Rule::requiredIf(function () use ($request) {
                    return $request->status !== 'draft';
                }),
            ],
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

        try {
            $announcement = Announcement::findOrFail($request->announcement_id);

            $announcement->update([
                'title' => $request->subject,
                'content' => $request->message ? $request->message : null,
                'start_date' => $request->start_date ? $request->start_date : null,
                'end_date' => $request->end_date ? $request->end_date : null,
                'recipient' => $request->visible_to,
                'status' => $request->status,
                'popup' => $request->popup === 'none' ? false : true,
                'popup_login' => $request->popup === 'none' ? null : $request->popup,
            ]);

            // Handle the profile photo
            if ($request->hasFile('thumbnail')) {
                $announcement->clearMediaCollection('thumbnail');
                $announcement->addMedia($request->thumbnail)->toMediaCollection('thumbnail');
            } elseif ($request->thumbnail === '') {
                // Clear the media if the profile_photo is an empty string
                $announcement->clearMediaCollection('thumbnail');
            }

            if ($announcement->visible_to !== 'public') {
                // Delete all existing UserAccountVisibility records for this account type
                UserAnnouncementVisibility::where('announcement_id', $announcement->id)->delete();
    
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
                'title' => trans("public.toast_edit_announcement_success"),
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

    public function deleteAnnouncement(Request $request)
    {
        $announcement = Announcement::find($request->announcement_id);

        try {
            $announcement->delete();

            return back()->with('toast', [
                'title' => trans("public.toast_delete_announcement_success"),
                'type' => 'success',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to delete announcement: ' . $e->getMessage());
            return back()->with('toast', [
                'title' => 'Failed to delete announcement',
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
