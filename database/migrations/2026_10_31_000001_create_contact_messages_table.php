<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Step 6c: messages from the public Contact form are saved here and read in the admin inbox. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // set when the sender was logged in
            $table->string('name', 80);
            $table->string('phone', 20)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('topic', 60);
            $table->text('message');
            $table->string('status', 20)->default('new')->index();
            $table->text('admin_note')->nullable(); // private, admins only
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
