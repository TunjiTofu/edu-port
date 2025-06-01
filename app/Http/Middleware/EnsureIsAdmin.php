<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();
        
        if (!$user || !$user->isAdmin()) {
            // Send notification before logout
            if ($user) {
                Notification::make()
                    ->title('Access Denied')
                    ->body('You do not have admin privileges to access this area.')
                    ->danger()
                    ->persistent()
                    ->send();
            }
            
            Filament::auth()->logout();
            
            // Redirect with session flash message
            return redirect()->guest(Filament::getLoginUrl())
                ->with('error', 'Admin privileges required to access this area.');
        }
        return $next($request);
    }
}
