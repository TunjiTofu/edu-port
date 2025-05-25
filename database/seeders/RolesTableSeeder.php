<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'System Administrator',
                'description' => 'Has full system access',
                'permissions' => ['*']
            ],
            [
                'name' => 'reviewer',
                'display_name' => 'Training Coordinator',
                'description' => 'Manages training programs and reviews submissions',
                'permissions' => ['manage-programs', 'review-submissions']
            ],
            [
                'name' => 'observer',
                'display_name' => 'Training Observer',
                'description' => 'Can view training programs and submissions',
                'permissions' => ['view-programs', 'view-submissions']
            ],
            [
                'name' => 'student',
                'display_name' => 'Trainee',
                'description' => 'Participates in training programs',
                'permissions' => ['view-programs', 'submit-tasks']
            ]
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
