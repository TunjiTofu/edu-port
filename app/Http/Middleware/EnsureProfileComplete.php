<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileComplete
{
    /**
     * Redirect candidates who have not fully completed their profile
     * (phone, church, district, passport photo, mg_mentor) to the
     * edit-profile page, with a Filament notification listing exactly
     * which fields are still missing.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if (! $user || ! $user->isStudent()) {
            return $next($request);
        }

        // Routes that must remain accessible even with an incomplete profile
        $skipPatterns = [
            'profile', 'edit', 'logout', 'login',
            'livewire', '_', 'assets',
        ];

        foreach ($skipPatterns as $pattern) {
            if (str_contains($request->path(), $pattern)) {
                return $next($request);
            }
        }

        if ($user->isProfileComplete()) {
            return $next($request);
        }

        // ── Build a specific list of what is missing ───────────────────────
        $missing = [];

        if (empty($user->phone))          $missing[] = '📞 Phone number';
        if (empty($user->mg_mentor))       $missing[] = '🎓 MG Mentor name';
        if (empty($user->passport_photo))  $missing[] = '🖼 Passport photo';
        if (is_null($user->church_id))     $missing[] = '⛪ Church';
        if (is_null($user->district_id))   $missing[] = '🗺 District';

        $missingList = implode(', ', $missing);

        Log::info('EnsureProfileComplete: redirecting incomplete profile', [
            'event'   => 'profile_incomplete_redirect',
            'user_id' => $user->id,
            'missing' => $missing,
            'path'    => $request->path(),
        ]);

        // ── Filament notification — shows in the panel UI ─────────────────
        // session()->flash() is not rendered by Filament's Livewire stack.
        // Filament\Notifications\Notification::make() sends via Livewire's
        // event system and shows as a toast on the redirected page.
        Notification::make()
            ->title('Profile Incomplete')
            ->body("Please fill in the following before continuing: {$missingList}.")
            ->warning()
            ->persistent() // stays until dismissed — a candidate must read it
            ->send();

        return redirect('/student/profiles/' . $user->id . '/edit');
    }
}
