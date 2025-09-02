<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class KycController extends Controller
{
    public function kyc_listing()
    {
        return Inertia::render('Member/KycListing');
    }

    public function getApprovedListing(Request $request)
    {
        $monthYear = $request->input('selectedMonth');

        if ($monthYear === 'select_all') {
            $startDate = Carbon::createFromDate(2020, 1, 1)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        } elseif (str_starts_with($monthYear, 'last_')) {
            preg_match('/last_(\d+)_week/', $monthYear, $matches);
            $weeks = $matches[1] ?? 1;

            $startDate = Carbon::now()->subWeeks($weeks)->startOfWeek();
            $endDate = Carbon::now()->subWeek($weeks)->endOfWeek();
        } else {
            $carbonDate = Carbon::parse($monthYear);

            $startDate = (clone $carbonDate)->startOfMonth()->startOfDay();
            $endDate = (clone $carbonDate)->endOfMonth()->endOfDay();
        }

        $query = User::with('teamHasUser.team', 'media')->where('kyc_approval', 'verified')->whereBetween('kyc_approved_at', [$startDate, $endDate]);

        $data = $query->orderByDesc('kyc_approved_at')
            ->get()->map(function ($user) {
                $media = $user->getMedia('kyc_verification');
                $submittedAt = $media->min('created_at'); // Use min() if there are 2 files

                return [
                    'name' => $user->chinese_name ?? $user->first_name,
                    'email' => $user->email,
                    'kyc_status' => $user->kyc_approval,
                    'approved_at' => $user->kyc_approved_at ?? null,
                    'team_id' => $user->teamHasUser->team_id ?? null,
                    'team_name' => $user->teamHasUser->team->name ?? null,
                    'team_color' => $user->teamHasUser->team->color ?? null,
                    'submitted_at' => $submittedAt,
                    'kyc_files' => $media,
                ];
            });

        return response()->json([
            'verifiedMembers' => $data,
        ]);
    }

}
