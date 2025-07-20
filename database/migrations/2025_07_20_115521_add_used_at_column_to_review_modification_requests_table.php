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
        Schema::table('review_modification_requests', function (Blueprint $table) {
            $table->timestamp('used_at')->nullable()->after('approved_at');
            $table->index(['review_id', 'status', 'used_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review_modification_requests', function (Blueprint $table) {
            $table->dropIndex(['review_id', 'status', 'used_at']);
            $table->dropColumn('used_at');
        });
    }
};
