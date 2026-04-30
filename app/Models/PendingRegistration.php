<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PendingRegistration extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Create a new pending registration and return the token.
     */
    public static function store(array $data, int $minutes = 15): string
    {
        // Clean up any expired entries for this email first
        static::where('email', $data['email'])
            ->orWhere('expires_at', '<', now())
            ->delete();

        $token = Str::uuid()->toString();

        static::create([
            'token'       => $token,
            'name'        => $data['name'],
            'email'       => $data['email'],
            'phone'       => $data['phone'],
            'mg_mentor'   => $data['mg_mentor'] ?? null,
            'district_id' => $data['district_id'],
            'church_id'   => $data['church_id'],
            'password'    => $data['password'],
            'passport_photo' => $data['passport_photo'] ?? null,
            'expires_at'  => now()->addMinutes($minutes),
        ]);

        return $token;
    }

    /**
     * Find a non-expired pending registration by token.
     * Returns null if not found or expired.
     */
    public static function findValid(string $token): ?static
    {
        return static::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Convert to an array matching the shape the controller expects.
     */
    public function toRegistrationData(): array
    {
        return [
            'name'           => $this->name,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'mg_mentor'      => $this->mg_mentor,
            'district_id'    => $this->district_id,
            'church_id'      => $this->church_id,
            'password'       => $this->password,
            'passport_photo' => $this->passport_photo,
        ];
    }
}
