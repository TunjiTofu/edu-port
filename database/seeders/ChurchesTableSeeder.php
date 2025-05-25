<?php

namespace Database\Seeders;

use App\Models\Church;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChurchesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $churches = [
            ['name' => 'No. 1'],
            ['name' => 'New Koregun'],
            ['name' => 'Iperu'],
        ];

        foreach ($churches as $church) {
            Church::create($church);
        }
    }
}
