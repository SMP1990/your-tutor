<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug', 100)->unique();          // public URL: /tutors/{slug}
            $table->string('status', 30)->default('draft')->index(); // see App\Enums\TutorStatus
            $table->string('headline', 160)->nullable();
            $table->text('bio')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('languages', 120)->nullable();
            $table->string('levels', 120)->nullable();       // short summary, e.g. "FSc · O Level"
            $table->unsignedTinyInteger('experience_years')->nullable();
            // Money in minor units (paisa / halala) with a currency code, ready for PKR and SAR.
            $table->unsignedInteger('hourly_rate_minor')->nullable();
            $table->char('currency', 3)->default('PKR');
            $table->string('photo_path')->nullable();        // Laravel storage path, never a full URL
            $table->string('intro_video_url')->nullable();
            $table->json('availability')->nullable();        // {"Mon": ["evening"], ...}
            $table->text('admin_notes')->nullable();         // visible to admin only
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subject_tutor_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unique(['tutor_profile_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_tutor_profile');
        Schema::dropIfExists('tutor_profiles');
    }
};
