<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRegistration
{
    public function __construct(protected RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Two-layer throttle:
        //   1. Per IP  — 5 attempts per 10 minutes
        //   2. Per email (if supplied) — 3 attempts per 10 minutes
        //      (prevents rotating IPs targeting the same email)

        $ipKey    = 'registration|ip|' . $request->ip();
        $emailKey = 'registration|email|' . strtolower($request->input('email', ''));

        foreach ([$ipKey, $emailKey] as $key) {
            if ($this->limiter->tooManyAttempts($key, $this->maxAttempts($key))) {
                $seconds = $this->limiter->availableIn($key);

                Log::warning('Registration rate limit hit', [
                    'key'  => $key,
                    'ip'   => $request->ip(),
                    'wait' => $seconds,
                ]);

                return $this->buildResponse($seconds);
            }

            // Increment hit count; decay after 10 minutes
            $this->limiter->hit($key, 600);
        }

        $response = $next($request);

        // On a successful registration (redirect away from form), clear the counters
        if ($response->isRedirection() && ! session()->has('errors')) {
            $this->limiter->clear($ipKey);
            $this->limiter->clear($emailKey);
        }

        return $response;
    }

    protected function maxAttempts(string $key): int
    {
        // Per-IP is looser (5) to allow households/offices with shared IPs
        // Per-email is tighter (3) to protect individual accounts
        return str_contains($key, '|ip|') ? 5 : 3;
    }

    protected function buildResponse(int $retryAfter): Response
    {
        // Return JSON for AJAX requests, redirect back for standard form POSTs
        if (request()->expectsJson()) {
            return response()->json([
                'message' => "Too many registration attempts. Please wait {$retryAfter} seconds before trying again.",
            ], 429);
        }

        return redirect()
            ->back()
            ->withInput()
            ->withErrors([
                'email' => "Too many registration attempts. Please wait " . ceil($retryAfter / 60) . " minute(s) before trying again.",
            ]);
    }
}
