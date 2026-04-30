<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $guarded = ['id'];

    // ── Static helpers ─────────────────────────────────────────────────────────

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("site_setting_{$key}", 300, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("site_setting_{$key}");
    }

    // ── Registration helpers ───────────────────────────────────────────────────

    /**
     * True when registration is open AND (no deadline set OR deadline is today or future).
     */
    public static function isRegistrationOpen(): bool
    {
        $open = static::get('registration_open', '1');
        if (! $open || $open === '0') return false;

        $deadline = static::get('registration_deadline');
        if (! $deadline) return true; // no deadline set — open indefinitely

        // Deadline is the LAST day registration is open.
        // After midnight on deadline+1, registration closes.
        return Carbon::parse($deadline)->endOfDay()->isFuture();
    }

    /**
     * Human-readable reason why registration is closed, for display on the form.
     */
    public static function registrationClosedMessage(): string
    {
        $open = static::get('registration_open', '1');
        if (! $open || $open === '0') {
            return 'Candidate registration is currently closed. Please check back later or contact your coordinator.';
        }

        $deadline = static::get('registration_deadline');
        if ($deadline && ! Carbon::parse($deadline)->endOfDay()->isFuture()) {
            $formatted = Carbon::parse($deadline)->format('F j, Y');
            return "The registration window closed on {$formatted}. New registrations are no longer being accepted for this program cycle. Please contact your district coordinator for assistance.";
        }

        return 'Registration is currently closed.';
    }
}
