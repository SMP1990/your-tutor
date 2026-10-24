<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            // Recorded by an admin (owner decision: subject test AND interview, entered by hand for now).
            $table->string('subject_test_result', 10)->nullable()->after('status');   // passed | failed
            $table->dateTime('interview_at')->nullable()->after('subject_test_result');
            $table->string('interview_link')->nullable()->after('interview_at');       // e.g. Google Meet
            $table->string('interview_result', 10)->nullable()->after('interview_link'); // passed | failed
            // Message shown to the tutor with the last decision (reason for rejection, what is missing).
            $table->text('decision_note')->nullable()->after('admin_notes');
            $table->foreignId('reviewed_by')->nullable()->after('decision_note')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['subject_test_result', 'interview_at', 'interview_link', 'interview_result', 'decision_note', 'reviewed_at']);
        });
    }
};
