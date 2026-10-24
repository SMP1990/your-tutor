<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Step 4a: after a good free demo, the family asks the tutor for regular weekly classes.
 * The rate is copied from the tutor at request time, so a later rate change does not alter it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // parent or student who booked
            $table->foreignId('child_id')->nullable()->constrained('children')->nullOnDelete();
            $table->foreignId('demo_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_name', 80);
            $table->string('grade', 40);
            $table->json('days');                     // e.g. ["Mon","Wed"]
            $table->string('start_time', 5);          // "17:00", site timezone
            $table->unsignedSmallInteger('duration_minutes');
            $table->date('starts_on');
            $table->unsignedSmallInteger('weeks');
            $table->unsignedInteger('rate_minor');    // per hour, copied from the tutor
            $table->string('currency', 3);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('expires_at')->index();
            $table->string('meet_link')->nullable();
            $table->text('tutor_note')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index(['tutor_profile_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_bookings');
    }
};
