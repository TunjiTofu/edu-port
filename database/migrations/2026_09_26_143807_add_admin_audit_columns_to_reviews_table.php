<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Audit trail for admin score/comment modifications.
            // The admin's changes become the live values; these columns
            // preserve what the original reviewer submitted for accountability.
            $table->decimal('original_score', 5, 2)->nullable()->after('score')
                ->comment('Reviewer\'s original score before admin modification');
            $table->text('original_comments')->nullable()->after('comments')
                ->comment('Reviewer\'s original comments before admin modification');
            $table->foreignId('admin_modified_by')->nullable()->after('original_comments')
                ->constrained('users')->nullOnDelete()
                ->comment('Admin who modified the review');
            $table->timestamp('admin_modified_at')->nullable()->after('admin_modified_by');
            $table->text('admin_modification_note')->nullable()->after('admin_modified_at')
                ->comment('Admin\'s reason for modifying the review');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_modified_by');
            $table->dropColumn([
                'original_score',
                'original_comments',
                'admin_modified_at',
                'admin_modification_note',
            ]);
        });
    }
};
