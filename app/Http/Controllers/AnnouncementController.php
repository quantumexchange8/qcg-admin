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
}
