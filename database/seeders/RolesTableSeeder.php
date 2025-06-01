<?php

namespace Database\Seeders;

use App\Enums\RoleTypes;
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
                'name' => RoleTypes::ADMIN->value,
                'display_name' => 'System Administrator',
                'description' => 'Has full system access',
                'permissions' => ['*']
            ],
            [
                'name' => RoleTypes::REVIEWER->value,
                'display_name' => 'Training Reviewer',
                'description' => 'Manages training programs and reviews submissions',
                'permissions' => ['manage-programs', 'review-submissions']
            ],
            [
                'name' => RoleTypes::OBSERVER->value,
                'display_name' => 'Training Observer',
                'description' => 'Can view training programs and submissions',
                'permissions' => ['view-programs', 'view-submissions']
            ],
            [
                'name' => RoleTypes::STUDENT->value,
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
