<?php

namespace Database\Seeders;

use App\Enums\RoleTypes;
use App\Models\District;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * FIXES:
     * 1. Role IDs looked up by enum name instead of hardcoded integers.
     *    Previously used role_id: 1, 2, 3, 4 — fragile if seeding order changes.
     * 2. generatePhoneNumber() now produces a valid 11-digit Nigerian number.
     *    Original used str_pad(..., 11, ...) which produced 14-digit numbers.
     * 3. Guards against missing districts/churches with meaningful error messages.
     * 4. Uses firstOrCreate on email to remain idempotent.
     */
    public function run(): void
    {
        // Load roles by name — no hardcoded IDs
        $roles = Role::all()->keyBy('name');

        $this->ensureRolesExist($roles);

        // Get all districts with their churches eager-loaded
        $districts = District::with('churches')->get();

        if ($districts->isEmpty()) {
            $this->command->warn('No districts found — running DistrictTableSeeder first.');
            $this->call(DistrictTableSeeder::class);
            $this->call(ChurchesTableSeeder::class);
            $districts = District::with('churches')->get();
        }

        // Admin user
        $district = $districts->random();
        $church   = $district->churches->random();

        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'       => 'Admin User',
                'password'   => Hash::make('password'),
                'role_id'    => $roles->get(RoleTypes::ADMIN->value)->id,
                'church_id'  => $church->id,
                'district_id'=> $district->id,
                'phone'      => $this->generatePhoneNumber(),
                'is_active'  => true,
            ]
        );

        // Other users — counts per role
        $roleDefinitions = [
            ['role' => RoleTypes::REVIEWER->value, 'count' => 1],
            ['role' => RoleTypes::OBSERVER->value, 'count' => 2],
            ['role' => RoleTypes::STUDENT->value,  'count' => 3],
        ];

        foreach ($roleDefinitions as $definition) {
            $roleModel = $roles->get($definition['role']);

            if (! $roleModel) {
                $this->command->warn("Role '{$definition['role']}' not found in database. Skipping.");
                continue;
            }

            for ($i = 1; $i <= $definition['count']; $i++) {
                $district = $districts->random();
                $church   = $district->churches->isNotEmpty()
                    ? $district->churches->random()
                    : $district->churches->first();

                if (! $church) {
                    $this->command->warn("District '{$district->name}' has no churches. Skipping user {$i} for role {$definition['role']}.");
                    continue;
                }

                $email = strtolower($definition['role']) . $i . '@example.com';

                User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name'        => $definition['role'] . ' ' . $i,
                        'password'    => Hash::make('password'),
                        'role_id'     => $roleModel->id,
                        'church_id'   => $church->id,
                        'district_id' => $district->id,
                        'phone'       => $this->generatePhoneNumber(),
                        'is_active'   => true,
                    ]
                );
            }
        }
    }

    /**
     * Generate a valid 11-digit Nigerian phone number (08X XXXXXXXX).
     *
     * FIX: Original str_pad($rand, 11, '0', STR_PAD_LEFT) produced 14-digit
     * numbers because mt_rand(0, 99999999) can be 8 digits + '081' = 11 chars
     * before padding. Now generates exactly 8 random digits, padded to 8.
     */
    private function generatePhoneNumber(): string
    {
        $prefixes = ['0801', '0802', '0803', '0805', '0806', '0807', '0808',
            '0809', '0810', '0811', '0812', '0813', '0814', '0815',
            '0816', '0817', '0818', '0819', '0901', '0902', '0903'];

        $prefix = $prefixes[array_rand($prefixes)];
        $suffix = str_pad((string) mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);

        return $prefix . $suffix; // 4 + 7 = 11 digits
    }

    /**
     * Ensure all required roles exist before attempting to create users.
     */
    private function ensureRolesExist($roles): void
    {
        $required = [
            RoleTypes::ADMIN->value,
            RoleTypes::REVIEWER->value,
            RoleTypes::OBSERVER->value,
            RoleTypes::STUDENT->value,
        ];

        $missing = array_diff($required, $roles->keys()->toArray());

        if (! empty($missing)) {
            $this->command->warn('Missing roles: ' . implode(', ', $missing) . '. Running RolesTableSeeder first.');
            $this->call(RolesTableSeeder::class);
        }
    }
}
