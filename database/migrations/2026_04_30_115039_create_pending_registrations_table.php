<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique()->index(); // the URL token
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('mg_mentor')->nullable();
            $table->unsignedBigInteger('district_id');
            $table->unsignedBigInteger('church_id');
            $table->string('password'); // already hashed before storing
            $table->string('passport_photo')->nullable();
            $table->timestamp('expires_at');  // hard expiry, cleaned up by scheduler
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};
