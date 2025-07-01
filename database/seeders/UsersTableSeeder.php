<?php

namespace Database\Seeders;

use App\Enums\RoleTypes;
use App\Models\District;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all districts with their churches
        $districts = District::with('churches')->get();

        // Admin user
        $district = $districts->random();
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
            'church_id' => $district->churches->random()->id,
            'district_id' => $district->id,
            'phone' => '08163513389'
        ]);

        // Other users
        $roles = [
            ['role' => 2, 'count' => 1],  // Reviewers
            ['role' => 3, 'count' => 2],  // Observers
            ['role' => 4, 'count' => 3], // Students
        ];

        foreach ($roles as $role) {
            for ($i = 0; $i < $role['count']; $i++) {
                $district = $districts->random();
                User::create([
                    'name' => $this->getRoleName($role['role']) . ' ' . ($i + 1),
                    'email' => strtolower($this->getRoleName($role['role'])) . ($i + 1) . '@example.com',
                    'password' => Hash::make('password'),
                    'role_id' => $role['role'],
                    'church_id' => $district->churches->random()->id,
                    'district_id' => $district->id,
                    'phone' => $this->generatePhoneNumber()
                ]);
            }
        }
    }

    private function getRoleName($roleId): string
    {
        return match ($roleId) {
            2 => RoleTypes::REVIEWER->value,
            3 => RoleTypes::OBSERVER->value,
            4 => RoleTypes::STUDENT->value,
            default => RoleTypes::STUDENT->value,
        };
    }

    private function generatePhoneNumber(): string
    {
        return '081' . str_pad(mt_rand(0, 99999999), 11, '0', STR_PAD_LEFT);
    }
}
