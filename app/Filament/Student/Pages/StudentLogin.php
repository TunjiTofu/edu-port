<?php

namespace App\Filament\Student\Pages;

use App\Models\User;
use Filament\Pages\Auth\Login;
use Illuminate\Validation\ValidationException;

class StudentLogin extends Login
{
    /**
     * Filament calls this method when authentication fails — including when
     * credentials are valid but canAccessPanel() returns false (deactivated,
     * disqualified, wrong role). By default it shows the generic
     * "These credentials do not match our records" message for all cases.
     *
     * We override it to show a specific, helpful message to:
     *   - Disqualified candidates
     *   - Deactivated accounts
     *
     * All other failures (wrong password, unknown email) still get the
     * generic message so we don't leak account existence information.
     */
    protected function throwFailureValidationException(): never
    {
        $email = $this->data['email'] ?? null;

        if ($email) {
            $user = User::where('email', $email)->first();

            if ($user) {
                // Disqualified — show the actual reason recorded by the admin,
                // with a fallback if no reason was provided.
                if ($user->isDisqualified()) {
                    $reason = $user->disqualification_reason
                        ? $user->disqualification_reason
                        : 'Your candidacy has been suspended. Please contact your administrator for more information.';

                    throw ValidationException::withMessages([
                        'data.email' => $reason,
                    ]);
                }

                // Deactivated — specific deactivation message
                if (! $user->is_active) {
                    throw ValidationException::withMessages([
                        'data.email' => 'Your account has been deactivated. Please contact your administrator.',
                    ]);
                }
            }
        }

        // Wrong password, unknown email, wrong role — generic message
        // (avoids leaking whether an email is registered)
        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}
