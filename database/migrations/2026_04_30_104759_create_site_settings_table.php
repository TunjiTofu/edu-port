<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('label')->nullable();       // human-readable label for admin UI
            $table->string('type')->default('string'); // string | date | boolean | integer
            $table->timestamps();
        });

        // Seed default settings
        DB::table('site_settings')->insert([
            [
                'key'        => 'registration_deadline',
                'value'      => null,
                'label'      => 'Candidate Registration Deadline',
                'type'       => 'date',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'        => 'registration_open',
                'value'      => '1',
                'label'      => 'Registration Open',
                'type'       => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
