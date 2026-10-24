<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // student | parent | tutor | admin (see App\Enums\Role)
            $table->string('role', 20)->default('student')->index()->after('email');
            // Saved now; verified later when an SMS/WhatsApp OTP provider is added.
            $table->string('phone', 20)->nullable()->index()->after('role');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->boolean('whatsapp_opt_in')->default(false)->after('phone_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['phone']);
            $table->dropColumn(['role', 'phone', 'phone_verified_at', 'whatsapp_opt_in']);
        });
    }
};
