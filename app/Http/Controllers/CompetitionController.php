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
            ->orderBy('start_date')
            ->get()
            ->map(function ($competition) {
                $name = json_decode($competition->name, true);

                return [
                    'competition_id' => $competition->id,
                    'category' => $competition->category,
                    'name' => $name,
                    'start_date' => $competition->start_date,
                    'end_date' => $competition->end_date,
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
            ->get()
            ->map(function ($competition) {
                $name = json_decode($competition->name, true);

                return [
                    'competition_id' => $competition->id,
                    'category' => $competition->category,
                    'name' => $name,
                    'start_date' => $competition->start_date,
                    'end_date' => $competition->end_date,
                    // 'total_points' => ,
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
            Log::info($start_at);
            Log::info($end_at);
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
}