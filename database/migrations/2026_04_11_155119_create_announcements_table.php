<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');

            // Who sees this on their dashboard:
            //   'all'       → all roles
            //   'candidate' → students only
            //   'reviewer'  → reviewers only
            //   'observer'  → observers only
            //   'admin'     → admins only
            $table->string('audience')->default('all');

            // Whether the admin also sent this via email and/or SMS
            $table->boolean('sent_email')->default(false);
            $table->boolean('sent_sms')->default(false);

            $table->timestamp('published_at')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['audience', 'is_published', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
