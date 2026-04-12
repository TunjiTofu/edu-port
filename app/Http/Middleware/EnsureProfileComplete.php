<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileComplete
{
    /**
     * Redirect candidates who have not fully completed their profile
     * (phone, church, district, passport photo) to the edit-profile page.
     *
     * Skipped for:
     *  - The profile edit page itself (avoids redirect loop)
     *  - Logout / login routes
     *  - Livewire / asset requests
     *  - Non-student users (other panels have their own middleware)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if (! $user || ! $user->isStudent()) {
            return $next($request);
        }

        // Routes that must remain accessible even with an incomplete profile
        $skipPatterns = [
            'profile',
            'edit',
            'logout',
            'login',
            'livewire',
            '_',
            'assets',
        ];

        foreach ($skipPatterns as $pattern) {
            if (str_contains($request->path(), $pattern)) {
                return $next($request);
            }
        }

        if ($user->isProfileComplete()) {
            return $next($request);
        }

        Log::info('EnsureProfileComplete: redirecting incomplete profile', [
            'event'      => 'profile_incomplete_redirect',
            'user_id'    => $user->id,
            'user_email' => $user->email,
            'missing'    => [
                'phone'          => empty($user->phone),
                'church'         => is_null($user->church_id),
                'district'       => is_null($user->district_id),
                'passport_photo' => empty($user->passport_photo),
            ],
            'path' => $request->path(),
            'ip'   => $request->ip(),
        ]);

        session()->flash('warning', 'Please complete your profile before accessing the portal.');

        return redirect('/student/profile/'. $user->id . '/edit');
    }
}
