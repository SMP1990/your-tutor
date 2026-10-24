<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Step 3a: a parent or student asks a tutor for a free demo class. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // who asked (parent or student)
            $table->foreignId('child_id')->nullable()->constrained('children')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_name', 80);
            $table->string('grade', 40);
            $table->string('preferred_day', 3);
            $table->string('preferred_time', 12);
            $table->string('phone', 20);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('expires_at')->index(); // a tutor who does not answer in time lets it expire
            $table->timestamps();

            $table->index(['tutor_profile_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_requests');
    }
};
