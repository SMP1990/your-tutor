<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Step 2c-2: an approved tutor's new subjects, education, photo or documents wait here for the admin;
 * everything else they change goes live at once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->json('pending_changes')->nullable()->after('degree_path');
            $table->timestamp('changes_submitted_at')->nullable()->index()->after('pending_changes');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->dropIndex(['changes_submitted_at']);
            $table->dropColumn(['pending_changes', 'changes_submitted_at']);
        });
    }
};
