<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Step 5a: a family reviews a tutor after at least one regular class was held.
 * One review per tutor per learner (child, or the student themself); it can be edited.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->nullable()->constrained('children')->nullOnDelete();
            $table->foreignId('class_booking_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment');
            $table->boolean('is_hidden')->default(false)->index(); // admin can hide (Step 5c)
            $table->timestamps();

            $table->index(['tutor_profile_id', 'is_hidden']);
            $table->index(['user_id', 'tutor_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
