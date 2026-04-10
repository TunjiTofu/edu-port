<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // $middleware->web([
        //     EnsureIsAdmin::class,
        //     EnsureUserIsStudent::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Friendly redirect for throttled candidate routes instead of a bare 429 page.
        // For all other routes, fall through and let Laravel render its default 429.
        $exceptions->render(function (ThrottleRequestsException $e, $request) {

            if ($request->is('candidate/register')) {
                return redirect()->route('candidate.register')
                    ->with('error',
                        'Too many registration attempts from your device. ' .
                        'Please wait a few minutes before trying again.'
                    );
            }

            if ($request->is('candidate/verify-otp')) {
                return redirect()->route('candidate.verify-otp')
                    ->with('error',
                        'Too many verification attempts. ' .
                        'Please wait a few minutes before trying again.'
                    );
            }

            if ($request->is('candidate/resend-otp')) {
                return redirect()->route('candidate.verify-otp')
                    ->with('error',
                        'You have requested too many new codes. ' .
                        'Please wait until your current code expires before requesting another.'
                    );
            }

            // All other routes — default 429 behaviour
            return null;
        });
    })->create();
