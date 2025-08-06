<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Competition;
use App\Models\CompetitionReward;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CompetitionController extends Controller
{
    public function index()
    {
        return Inertia::render('Competition/Competition');
    }

    public function newCompetition()
    {
        return Inertia::render('Competition/Partials/NewCompetition');

    }

    public function getCurrentCompetitions()
    {
        $competitions = Competition::whereNot('status', 'completed')
            ->with('rewards')
            ->orderBy('start_date')
            ->get()
            ->map(function ($competition) {
                $name = json_decode($competition->name, true);

                $totalPointsDistributed = $competition->rewards->sum(function ($reward) {
                    $numberOfRanksInTier = ($reward->max_rank - $reward->min_rank + 1);
        
                    return $numberOfRanksInTier * $reward->points_rewarded;
                });

                return [
                    'competition_id' => $competition->id,
                    'category' => $competition->category,
                    'name' => $name,
                    'status' => $competition->status,
                    'start_date' => $competition->start_date,
                    'end_date' => $competition->end_date,
                    'total_points' => $totalPointsDistributed,
                ];
            })
            ->values();

        return response()->json([
            'competitions' => $competitions,
        ]);
    }

    public function getCompetitionHistory(Request $request)
    {
        $category = $request->query('category');
        $query = Competition::where('status', 'completed');

        if ($category) {
            $query->where('category', $category);
        }

        $competitions = $query->orderBy('end_date')
            ->with('rewards')
            ->get()
            ->map(function ($competition) {
                $name = json_decode($competition->name, true);

                $totalPointsDistributed = $competition->rewards->sum(function ($reward) {
                    $numberOfRanksInTier = ($reward->max_rank - $reward->min_rank + 1);
        
                    return $numberOfRanksInTier * $reward->points_rewarded;
                });

                return [
                    'competition_id' => $competition->id,
                    'category' => $competition->category,
                    'name' => $name,
                    'start_date' => $competition->start_date,
                    'end_date' => $competition->end_date,
                    'total_points' => $totalPointsDistributed,
                ];
            })
            ->values();

        return response()->json([
            'competitions' => $competitions,
        ]);
    }

    public function createCompetition(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => ['required'],
            'name' => ['required', 'array'],
            'name.*' => ['required', 'string'],
            'start_date' => ['required'],
            'start_time' => ['required'],
            'end_date' => ['required'],
            'end_time' => ['required'],
            'unranked_badge' => ['required'],
        ])->setAttributeNames([
            'rewards_type' => trans('public.rewards_type'),
            'name.*' => trans('public.name'),
            'start_date' => trans('public.start_date'),
            'start_time' => trans('public.start_time'),
            'end_date' => trans('public.end_date'),
            'end_time' => trans('public.end_time'),
            'unranked_badge' => trans('public.unranked_badge'),
        ]);
        $validator->validate();

        try {
            $start_at = Carbon::parse($request->start_date)->format('Y-m-d') . ' ' . Carbon::parse($request->start_time)->format('H:i:s');
            $end_at = Carbon::parse($request->end_date)->format('Y-m-d') . ' ' . Carbon::parse($request->end_time)->format('H:i:s');
            // Log::info($start_at);
            // Log::info($end_at);
            $start_at = Carbon::parse($start_at);
            $end_at = Carbon::parse($end_at);

            $competition = Competition::create([
                'category' => $request->category,
                'name' => json_encode($request->name),
                'start_date' => $start_at,
                'end_date' => $end_at,
                'minimum_amount' => $request->min_amount,
                'status' => 'inactive',
            ]);

            if ($request->hasFile('unranked_badge')) {
                $competition->addMedia($request->unranked_badge)
                            ->toMediaCollection('unranked_badge');
    
            } elseif ($request->filled('unranked_badge') && is_string($request->unranked_badge)) {
                $localFilePath = public_path($request->unranked_badge);
    
                if (file_exists($localFilePath)) {
                    $competition->addMedia($localFilePath)
                                ->preservingOriginal()
                                ->toMediaCollection('unranked_badge');
                } else {
                    Log::warning('Spatie Media Library: Default unranked badge not found on server at: ' . $localFilePath);
                }
            }

            foreach ($request['rewards'] as $reward){
                $competition_reward = CompetitionReward::create([
                    'competition_id' => $competition->id,
                    'min_rank' => $reward['min_rank'],
                    'max_rank' => $reward['max_rank'],
                    'points_rewarded' => $reward['points_rewarded'],
                    'title' => json_encode($reward['title']),
                ]);

                if (isset($reward['_uploadedBadgeFile'])) {
                    $competition_reward->addMedia($reward['_uploadedBadgeFile'])
                                ->toMediaCollection('rank_badge');
        
                } elseif (!empty($reward['rank_badge']) && is_string($reward['rank_badge'])) {
                    $localFilePath = public_path($reward['rank_badge']);
        
                    if (file_exists($localFilePath)) {
                        $competition_reward->addMedia($localFilePath)
                                    ->preservingOriginal()
                                    ->toMediaCollection('rank_badge');
                    } else {
                        Log::warning('Spatie Media Library: Default unranked badge not found on server at: ' . $localFilePath);
                    }
                }
            }

            // Redirect with success message
            return redirect()->route('competition')->with('toast', [
                'title' => trans("public.toast_create_competition_success"),
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            // Log the exception and show a generic error message
            Log::error('Error creating the competition : '.$e->getMessage());

            return redirect()->route('competition')->with('toast', [
                'title' => 'There was an error creating the competition.',
                'type' => 'error'
            ]);
        }

    }

    public function deleteCompetition(Request $request)
    {
        $competition = Competition::find($request->id);

        try {
            if ($competition) {
                $competition->load('rewards');
            
                foreach ($competition->rewards as $reward) {
                    $reward->clearMediaCollection('rank_badge');
                }

                // note to self: deleting this way is more efficient than for each
                $competition->rewards()->delete();
            }

            $competition->clearMediaCollection('unranked_badge');
            $competition->delete();

            return back()->with('toast', [
                'title' => trans("public.toast_competition_deleted"),
                'type' => 'success',
            ]);

        } catch (\Exception $e) {
            // Log the exception and show a generic error message
            Log::error('Error deleting the competition : '.$e->getMessage());

            return back()->with('toast', [
                'title' => 'There was an error deleting the competition.',
                'type' => 'error'
            ]);
        }
    }

    public function editCompetition(Request $request, string $id) {
        $competition = Competition::with('rewards')->findOrFail($request->id);

        $decodedCompetitionName = json_decode($competition->name, true);
        $unranked_badge = $competition->getFirstMediaUrl('unranked_badge');

        $transformedRewards = $competition->rewards->map(function (CompetitionReward $reward) {
            $decodedRewardTitle = json_decode($reward->title, true);
            $rank_badge = $reward->getFirstMediaUrl('rank_badge');

            return [
                'id' => $reward->id,
                'min_rank' => $reward->min_rank,
                'max_rank' => $reward->max_rank,
                'points_rewarded' => $reward->points_rewarded,
                'title' => $decodedRewardTitle,
                'rank_badge' => $rank_badge,
            ];
        });

        $finalCompetitionData = [
            'competition_id' => $competition->id,
            'category' => $competition->category,
            'name' => $decodedCompetitionName,
            'start_date' =>  Carbon::parse($competition->start_date)->format('Y-m-d'),
            'start_time' => Carbon::parse($competition->start_date)->format('H:i'),
            'end_date' => Carbon::parse($competition->end_date)->format('Y-m-d'),
            'end_time' => Carbon::parse($competition->end_date)->format('H:i'),
            'minimum_amount' => $competition->minimum_amount,
            'rewards' => $transformedRewards,
            'unranked_badge' => $unranked_badge,
        ];

        return Inertia::render('Competition/Partials/EditCompetition', [
            'competition' => $finalCompetitionData,
        ]);
    }

    public function saveCompetition(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => ['required'],
            'name' => ['required', 'array'],
            'name.*' => ['required', 'string'],
            'start_date' => ['required'],
            'start_time' => ['required'],
            'end_date' => ['required'],
            'end_time' => ['required'],
            'unranked_badge' => ['required'],
        ])->setAttributeNames([
            'rewards_type' => trans('public.rewards_type'),
            'name.*' => trans('public.name'),
            'start_date' => trans('public.start_date'),
            'start_time' => trans('public.start_time'),
            'end_date' => trans('public.end_date'),
            'end_time' => trans('public.end_time'),
            'unranked_badge' => trans('public.unranked_badge'),
        ]);
        $validator->validate();

        try {
            $start_at = Carbon::parse($request->start_date)->format('Y-m-d') . ' ' . Carbon::parse($request->start_time)->format('H:i:s');
            $end_at = Carbon::parse($request->end_date)->format('Y-m-d') . ' ' . Carbon::parse($request->end_time)->format('H:i:s');
            // Log::info($start_at);
            // Log::info($end_at);
            $start_at = Carbon::parse($start_at);
            $end_at = Carbon::parse($end_at);

            $competition = Competition::findOrFail($request->competition_id);

            $competition->update([
                'category' => $request->category,
                'name' => json_encode($request->name),
                'start_date' => $start_at,
                'end_date' => $end_at,
                'minimum_amount' => $request->min_amount,
                'status' => 'inactive',
            ]);

            if ($request->hasFile('unranked_badge')) {
                $competition->clearMediaCollection('unranked_badge');

                $competition->addMedia($request->unranked_badge)
                            ->toMediaCollection('unranked_badge');
            }

            $existingRewardIds = $competition->rewards->pluck('id');

            $incomingRewardIds = collect($request->rewards)
                ->pluck('id')
                ->filter(); 

            $rewardsToDelete = $existingRewardIds->diff($incomingRewardIds);

            if ($rewardsToDelete->isNotEmpty()) {
                $rewardsToClearAndDelete = CompetitionReward::whereIn('id', $rewardsToDelete)->get();
                foreach ($rewardsToClearAndDelete as $reward) {
                    $reward->clearMediaCollection('rank_badge');
                }
                CompetitionReward::whereIn('id', $rewardsToDelete)->delete();
            }

            foreach ($request['rewards'] as $reward) {
                Log::info($reward['id']);
                if (isset($reward['id']) && $existingRewardIds->contains($reward['id'])) {
                    $competition_reward = CompetitionReward::findOrFail($reward['id']);

                    $competition_reward->update([
                        'min_rank' => $reward['min_rank'],
                        'max_rank' => $reward['max_rank'],
                        'points_rewarded' => $reward['points_rewarded'],
                        'title' => json_encode($reward['title']),
                    ]);

                    if (isset($reward['_uploadedBadgeFile'])) {
                        $competition_reward->clearMediaCollection('rank_badge');

                        $competition_reward->addMedia($reward['_uploadedBadgeFile'])
                            ->toMediaCollection('rank_badge');
                    }
                } else {
                    $competition_reward = CompetitionReward::create([
                        'competition_id' => $competition->id,
                        'min_rank' => $reward['min_rank'],
                        'max_rank' => $reward['max_rank'],
                        'points_rewarded' => $reward['points_rewarded'],
                        'title' => json_encode($reward['title']),
                    ]);

                    if (isset($reward['_uploadedBadgeFile'])) {
                        $competition_reward->addMedia($reward['_uploadedBadgeFile'])
                                    ->toMediaCollection('rank_badge');
            
                    } elseif (!empty($reward['rank_badge']) && is_string($reward['rank_badge'])) {
                        $localFilePath = public_path($reward['rank_badge']);
            
                        if (file_exists($localFilePath)) {
                            $competition_reward->addMedia($localFilePath)
                                        ->preservingOriginal()
                                        ->toMediaCollection('rank_badge');
                        } else {
                            Log::warning('Spatie Media Library: Default unranked badge not found on server at: ' . $localFilePath);
                        }
                    }
                }
            }


            // Redirect with success message
            return redirect()->route('competition')->with('toast', [
                'title' => trans("public.toast_edit_competition_success"),
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            // Log the exception and show a generic error message
            Log::error('Error editing the competition : '.$e->getMessage());

            return redirect()->route('competition')->with('toast', [
                'title' => 'There was an error editing the competition.',
                'type' => 'error'
            ]);
        }
    }

    public function viewCompetition(Request $request, string $id)
    {
        $competition = Competition::with('rewards')->findOrFail($request->id);

        $name = json_decode($competition->name, true); 

        $competitionData = [
            'competition_id' => $competition->id,
            'category' => $competition->category,
            'name' => $name,
            'start_datetime' =>  Carbon::parse($competition->start_date)->format('Y-m-d H:i'),
            'end_datetime' => Carbon::parse($competition->end_date)->format('Y-m-d H:i'),
            'minimum_amount' => $competition->minimum_amount,
        ];

        return Inertia::render('Competition/Partials/ViewCompetition', [
            'competition' => $competitionData,
        ]);
    }


    public function getParticipants(Request $request)
    {
        $participants = Participant::with(['user', 'competition.rewards'])
            ->where('competition_id', $request->competition_id)
            ->orderByDesc('score')
            ->get()
            ->map(function ($participant, $key) {
            $participantRank = $key + 1;

            $reward = $participant->competition->rewards->first(function ($reward) use ($participantRank) {
                return $participantRank >= $reward->min_rank && $participantRank <= $reward->max_rank;
            });

            $rankTitle = null;
            $points = 0;
            if ($reward) {
                $rankTitle = json_decode($reward->title, true);
                $points = $reward->points_rewarded;
            }

            $name = null;
            if ($participant->user_type == 'standard') {
                $name = $participant->user?->chinese_name ?? $participant->user?->first_name;
            } else {
                $name = $participant->user_name;
            }

            return [
                'id' => $participant->id,
                'user_type' => $participant->user_type,
                'name' => $name,
                'score' => $participant->score,
                'rank' => $participantRank,
                'title' => $rankTitle,
                'points_rewarded' => $points,
            ];
        });

        Log::info($participants);

        return response()->json([
            'participants' => $participants,
        ]);
    }

    public function addVirtual(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'virtual_name' => ['required'],
            'amount' => ['required'],
        ])->setAttributeNames([
            'virtual_name' => trans('public.name'),
            'amount' => trans('public.amount'),
        ]);
        $validator->validate();

        try {
            Participant::create([
                'competition_id' => $request->competition_id,
                'user_type' => 'virtual',
                'user_name' => $request->virtual_name,
                'score' => $request->amount,
            ]);

            // Redirect with success message
            return back()->with('toast', [
                'title' => trans("public.toast_add_participant_success"),
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            // Log the exception and show a generic error message
            Log::error('Error adding participant : '.$e->getMessage());

            return back()->with('toast', [
                'title' => 'There was an error adding the participant.',
                'type' => 'error'
            ]);
        }
    }

    public function editVirtual(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'virtual_name' => ['required'],
            'amount' => ['required'],
        ])->setAttributeNames([
            'virtual_name' => trans('public.name'),
            'amount' => trans('public.amount'),
        ]);
        $validator->validate();

        try {
            $participant = Participant::findOrFail($request->participant_id);

            $participant->update([
                'user_name' => $request->virtual_name,
                'score' => $request->amount,
            ]);

            // Redirect with success message
            return back()->with('toast', [
                'title' => trans("public.toast_edit_participant_success"),
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            // Log the exception and show a generic error message
            Log::error('Error editing participant : '.$e->getMessage());

            return back()->with('toast', [
                'title' => 'There was an error editing the participant.',
                'type' => 'error'
            ]);
        }
    }

    public function deleteVirtual(Request $request)
    {
        try {
            $participant = Participant::findOrFail($request->participant_id);
            
            $participant->delete();

            // Redirect with success message
            return back()->with('toast', [
                'title' => trans("public.toast_delete_participant_success"),
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            // Log the exception and show a generic error message
            Log::error('Error deleting participant : '.$e->getMessage());

            return back()->with('toast', [
                'title' => 'There was an error deleting the participant.',
                'type' => 'error'
            ]);
        }
    }
}