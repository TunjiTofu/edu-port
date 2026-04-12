<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        // ── Not authenticated or wrong role ────────────────────────────────
        if (! $user || ! $user->isStudent()) {
            if ($user) {
                Log::warning('EnsureUserIsStudent: non-candidate access attempt', [
                    'event'   => 'candidate_panel_access_denied',
                    'user_id' => $user->id,
                    'role'    => $user->role?->name,
                    'ip'      => $request->ip(),
                ]);
                Notification::make()
                    ->title('Access Denied')
                    ->body('You do not have candidate privileges to access this area.')
                    ->danger()->persistent()->send();
            }
            Filament::auth()->logout();
            return redirect()->guest(Filament::getLoginUrl())
                ->with('error', 'Candidate privileges required.');
        }

        // ── Deactivated account ────────────────────────────────────────────
        if (! $user->is_active) {
            Log::warning('EnsureUserIsStudent: deactivated account login attempt', [
                'event'   => 'candidate_account_deactivated',
                'user_id' => $user->id,
                'ip'      => $request->ip(),
            ]);
            Filament::auth()->logout();
            return redirect()->guest(Filament::getLoginUrl())
                ->with('error', 'Your account has been deactivated. Please contact your administrator.');
        }

        // ── Disqualified candidate ─────────────────────────────────────────
        if ($user->isDisqualified()) {
            Log::warning('EnsureUserIsStudent: disqualified candidate login attempt', [
                'event'   => 'candidate_disqualified_login',
                'user_id' => $user->id,
                'ip'      => $request->ip(),
            ]);
            Filament::auth()->logout();
            return redirect()->guest(Filament::getLoginUrl())
                ->with('error', 'Your candidacy has been suspended. Please contact your administrator for more information.');
        }

        return $next($request);
    }
}
