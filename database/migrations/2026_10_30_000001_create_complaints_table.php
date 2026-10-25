<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Step 6b: "Report a problem" on a booking or a single class, from the family or the tutor.
 * admin_note is private; admin_reply is shown to the person who reported.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // who reported
            $table->foreignId('class_booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 30);
            $table->text('details');
            $table->string('status', 20)->default('new')->index();
            $table->text('admin_note')->nullable(); // private, admins only
            $table->text('admin_reply')->nullable(); // the reporter sees this
            $table->timestamp('replied_at')->nullable();
            $table->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['class_booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
