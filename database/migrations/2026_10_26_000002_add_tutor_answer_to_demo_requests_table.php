<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Step 3b: the tutor accepts (time + Google Meet link) or declines (reason). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->index()->after('expires_at'); // UTC
            $table->string('meet_link')->nullable()->after('scheduled_at');
            $table->text('tutor_note')->nullable()->after('meet_link');
            $table->timestamp('answered_at')->nullable()->after('tutor_note');
        });
    }

    public function down(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->dropIndex(['scheduled_at']);
            $table->dropColumn(['scheduled_at', 'meet_link', 'tutor_note', 'answered_at']);
        });
    }
};
