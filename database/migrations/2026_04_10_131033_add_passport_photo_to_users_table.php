<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Passport photo path — stored on configured disk
            $table->string('passport_photo')->nullable()->after('phone');

            // Null means profile is incomplete. Set to now() when candidate
            // has filled phone, church, district AND uploaded a passport photo.
            $table->timestamp('profile_completed_at')->nullable()->after('passport_photo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['passport_photo', 'profile_completed_at']);
        });
    }
};
