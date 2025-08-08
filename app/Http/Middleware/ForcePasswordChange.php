<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        // Skip if user is not authenticated
        if (!$user) {
            return $next($request);
        }

        // Skip if user has already changed their password
        if ($user->password_updated_at) {
            return $next($request);
        }



        // Skip if already on password change page to avoid redirect loop
        if ($request->routeIs('filament.*.pages.change-password') ||
            $request->routeIs('filament.*.resources.change-password.*')) {
            return $next($request);
        }


        // Skip for logout requests
        if ($request->routeIs('filament.*.auth.logout')) {
            return $next($request);
        }

        // Determine which panel the user is accessing
        $panelId = $this->getPanelId($request);

        // Redirect to appropriate change password page based on user role
//        return match($user->role->name) {
//            'Student' => redirect()->route('filament.student.pages.change-password'),
//            'Reviewer' => redirect()->route('filament.reviewer.pages.change-password'),
//            'Observer' => redirect()->route('filament.observer.pages.change-password'),
//            default => $next($request)
//        };

        return match($user->role->name) {
            'Student' => redirect('/student/change-password'),
            'Reviewer' => redirect('/reviewer/change-password'),
            'Observer' => redirect('/observer/change-password'),
            default => $next($request)
        };
    }

    private function getPanelId(Request $request): ?string
    {
        $path = $request->path();

        if (str_starts_with($path, 'student/')) {
            return 'student';
        } elseif (str_starts_with($path, 'reviewer/')) {
            return 'reviewer';
        } elseif (str_starts_with($path, 'observer/')) {
            return 'observer';
        }

        return null;
    }
}
