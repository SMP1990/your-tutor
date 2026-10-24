<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Step 4c: who marked the class, when, and an optional reason (for cancellations). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->string('note', 300)->nullable()->after('status');
            $table->foreignId('marked_by')->nullable()->after('note')->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable()->after('marked_by');
        });
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marked_by');
            $table->dropColumn(['note', 'marked_at']);
        });
    }
};
