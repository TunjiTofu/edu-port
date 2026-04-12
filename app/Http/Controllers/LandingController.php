<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        // Only show announcements broadcast to 'all' — role-specific ones
        // are for logged-in users on their panel dashboard.
        $announcements = Announcement::published()
            ->forAudience('all')
            ->latestFirst()
            ->limit(5)
            ->get();

        $tutorials = config('tutorials', []);

        return view('landing', compact('announcements', 'tutorials'));
    }
}
