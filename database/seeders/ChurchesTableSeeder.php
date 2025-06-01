<?php

namespace Database\Seeders;

use App\Models\Church;
use App\Models\District;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ChurchesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all districts once
        $districts = District::all();

        // Ensure we have districts before creating churches
        if ($districts->isEmpty()) {
            $this->call(DistrictTableSeeder::class);
            $districts = District::all();
        }

        // Define base churches with district association
        $baseChurches = [
            'No. 1' => 'Ilishan-West District',
            'New Koregun' => 'Ilishan-West District',
            'Pioneer' => 'Babcock',
            'Heritage' => 'Babcock',
            'Express' => 'Shagamu',
            'Sabo' => 'Shagamu',
        ];

        // Create base churches with specific district relationships
        foreach ($baseChurches as $churchName => $districtName) {
            Church::firstOrCreate([
                'name' => $churchName,
                'district_id' => $districts->firstWhere('name', $districtName)->id,
                'is_active' => true,
            ]);
        }

        // // Create additional churches for each district
        // $districts->each(function ($district) {
        //     Church::factory()->count(2)->create([
        //         'district_id' => $district->id,
        //         'is_active' => true,
        //     ]);
        // });
    }
}
