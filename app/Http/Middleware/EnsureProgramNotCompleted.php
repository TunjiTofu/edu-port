<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureProgramNotCompleted
{
    /**
     * Graduated candidates (program_completed_at is set) are locked to
     * read-only mode. They can view their results and announcements but
     * cannot submit, edit their profile, or enroll in new programs.
     *
     * Read-only paths allowed for graduated candidates:
     *  - dashboard
     *  - results / performance
     *  - profile VIEW (not edit)
     *  - training-programs VIEW
     *
     * Blocked for graduated candidates:
     *  - profile edit
     *  - tasks (submit / resubmit)
     *  - available-training-programs (enroll)
     *  - any POST / PATCH / DELETE that isn't logout
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if (! $user || ! $user->isStudent()) {
            return $next($request);
        }

        if (! $user->hasCompletedProgram()) {
            return $next($request);
        }

        $path = $request->path();

        // Always allow these — login, logout, assets, Livewire wire calls
        $alwaysAllow = [
            'login', 'logout', 'livewire', '_', 'assets',
            // Read-only views
            'dashboard', 'results', 'performance',
        ];

        foreach ($alwaysAllow as $pattern) {
            if (str_contains($path, $pattern)) {
                return $next($request);
            }
        }

        // Block write-intent routes
        $blockedPatterns = [
            'available-training-programs',  // enrollment
            'tasks',                        // submission
            'profile',                      // profile edit (view is ok)
            'submissions',                  // any submission action
        ];

        // Allow GET (viewing) on profile and training programs
        if ($request->isMethod('GET') && ! str_contains($path, 'available-training-programs')) {
            return $next($request);
        }

        foreach ($blockedPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                Log::info('EnsureProgramNotCompleted: blocked graduated candidate', [
                    'event'   => 'graduated_access_blocked',
                    'user_id' => $user->id,
                    'path'    => $path,
                    'method'  => $request->method(),
                ]);

                if ($request->expectsJson() || str_contains($path, 'livewire')) {
                    return response()->json([
                        'message' => 'Your program is complete. You no longer have access to this feature.',
                    ], 403);
                }

                session()->flash('warning',
                    'Your program has been marked as complete. You can view your results and announcements but cannot make new submissions or changes.'
                );

                return redirect('/student');
            }
        }

        return $next($request);
    }
}
