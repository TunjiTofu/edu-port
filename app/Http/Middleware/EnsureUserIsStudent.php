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

        if (! $user || ! $user->isStudent()) {
            if ($user) {
                Log::warning('EnsureUserIsStudent: non-candidate attempted to access candidate panel', [
                    'event'      => 'candidate_panel_access_denied',
                    'user_id'    => $user->id,
                    'user_email' => $user->email,
                    'role'       => $user->role?->name ?? 'unknown',
                    'path'       => $request->path(),
                    'ip'         => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                Notification::make()
                    ->title('Access Denied')
                    ->body('You do not have candidate privileges to access this area.')
                    ->danger()
                    ->persistent()
                    ->send();
            } else {
                Log::info('EnsureUserIsStudent: unauthenticated request to candidate panel', [
                    'event' => 'candidate_panel_unauthenticated',
                    'path'  => $request->path(),
                    'ip'    => $request->ip(),
                ]);
            }

            Filament::auth()->logout();

            return redirect()->guest(Filament::getLoginUrl())
                ->with('error', 'Candidate privileges required to access this area.');
        }

        return $next($request);
    }
}
