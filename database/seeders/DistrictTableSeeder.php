<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * FIX: Changed District::create() to District::firstOrCreate() so this
     * seeder is idempotent — safe to run multiple times without throwing
     * unique constraint violations on name/code columns.
     */
    public function run(): void
    {
        $districts = [
            [
                'name'        => 'Ilishan-West District',
                'code'        => 'IWD',
                'description' => '',
                'is_active'   => true,
            ],
            [
                'name'        => 'Babcock',
                'code'        => 'BD',
                'description' => '',
                'is_active'   => true,
            ],
            [
                'name'        => 'Shagamu',
                'code'        => 'SD',
                'description' => '',
                'is_active'   => true,
            ],
        ];

        foreach ($districts as $district) {
            // Match on unique columns; only set remaining columns on first create
            District::firstOrCreate(
                ['code' => $district['code']],        // unique lookup key
                [
                    'name'        => $district['name'],
                    'description' => $district['description'],
                    'is_active'   => $district['is_active'],
                ]
            );
        }
    }
}
