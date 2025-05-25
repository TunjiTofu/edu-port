<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsObserver
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();
        
        if (!$user || !$user->isObserver()) {
            // Send notification before logout
            if ($user) {
                Notification::make()
                    ->title('Access Denied')
                    ->body('You do not have observer privileges to access this area.')
                    ->danger()
                    ->persistent()
                    ->send();
            }
            
            Filament::auth()->logout();
            
            // Redirect with session flash message
            return redirect()->guest(Filament::getLoginUrl())
                ->with('error', 'Observer privileges required to access this area.');
        }
        
        return $next($request);
    }
}
