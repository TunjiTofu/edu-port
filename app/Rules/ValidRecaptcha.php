<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ValidRecaptcha implements ValidationRule
{
    /**
     * Minimum reCAPTCHA v3 score to accept.
     * 0.0 = definitely bot, 1.0 = definitely human.
     * 0.5 is Google's recommended threshold.
     */
    protected float $threshold;

    public function __construct(float $threshold = 0.5)
    {
        $this->threshold = $threshold;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Skip in testing environment so unit tests don't hit Google
        if (app()->environment('testing')) {
            return;
        }

        if (empty($value)) {
            $fail('The security check failed. Please refresh and try again.');
            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret'   => config('recaptcha.secret_key'),
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

            $result = $response->json();

            // Log for visibility — remove in production if too noisy
            Log::info('reCAPTCHA result', [
                'success'    => $result['success'] ?? false,
                'score'      => $result['score'] ?? null,
                'action'     => $result['action'] ?? null,
                'ip'         => request()->ip(),
            ]);

            if (
                ! ($result['success'] ?? false) ||
                ($result['score'] ?? 0) < $this->threshold
            ) {
                $fail('The security check failed. Please try again.');
            }

        } catch (\Throwable $e) {
            // If Google is unreachable, log it but fail open (don't block real users)
            // Switch to fail-closed ($fail(...)) if you prefer strict security
            Log::error('reCAPTCHA verification error', ['error' => $e->getMessage()]);
        }
    }
}
