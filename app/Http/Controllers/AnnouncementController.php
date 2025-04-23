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

    // public function deleteReward(Request $request)
    // {
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

    // }

}
