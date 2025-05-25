<?php

namespace Database\Seeders;

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
        // Admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
            'church_id' => 1,
            'district_id' => 1,
            'phone' => '08163513389'
        ]);

        // Trainer users
        User::factory()->count(3)->create([
            'role_id' => 2,
            'church_id' => rand(1, 3),
            'district_id' => rand(1, 3),
            'password' => Hash::make('password'),
            'phone' => '08143452621'
        ]);

        // Observer users
        User::factory()->count(2)->create([
            'role_id' => 3,
            'church_id' => rand(1, 3),
            'district_id' => rand(1, 3),
            'password' => Hash::make('password'),
            'phone' => '081000000000'
        ]);

        // Student users
        User::factory()->count(10)->create([
            'role_id' => 4,
            'church_id' => rand(1, 3),
            'district_id' => rand(1, 3),
            'password' => Hash::make('password'),
            'phone' => '081234567890'
        ]);
    }
}
