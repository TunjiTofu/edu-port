<?php

namespace App\Http\Middleware;

use App\Enums\RoleTypes;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->password_updated_at) {
            return $next($request);
        }

        $path = $request->path();

        // IMPORTANT: 'profile' and 'edit' must be here — EnsureProfileComplete
        // redirects to the profile edit page, and if ForcePasswordChange then
        // redirects away from it, the two middlewares create an infinite loop.
        $skipPatterns = ['change-password', 'logout', 'login', 'profile', 'edit'];

        foreach ($skipPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return $next($request);
            }
        }

        if ($request->is('api/*') || $request->is('_*') || $request->is('assets/*') || $request->is('livewire/*')) {
            return $next($request);
        }

        $roleName = $user->role?->name;

        $redirectPath = match ($roleName) {
            RoleTypes::STUDENT->value  => '/student/change-password',
            RoleTypes::REVIEWER->value => '/reviewer/change-password',
            RoleTypes::OBSERVER->value => '/observer/change-password',
            default                    => null,
        };

        if ($redirectPath) {
            Log::info('ForcePasswordChange: redirecting user to change default password', [
                'event'         => 'force_password_change_redirect',
                'user_id'       => $user->id,
                'user_email'    => $user->email,
                'role'          => $roleName,
                'redirect_to'   => $redirectPath,
                'attempted_path'=> $path,
                'ip'            => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ]);

            return redirect($redirectPath);
        }

        return $next($request);
    }
}
