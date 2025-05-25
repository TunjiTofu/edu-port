<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DistrictTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $districts = [
            ['name' => 'Ilishan-West District', 'code' => 'IWD', 'description' => ''],
            ['name' => 'Babcock', 'code' => 'BD', 'description' => ''],
            ['name' => 'Shagamu', 'code' => 'SD', 'description' => ''],
        ];

        foreach ($districts as $district) {
            District::create($district);
        }
    }
}
