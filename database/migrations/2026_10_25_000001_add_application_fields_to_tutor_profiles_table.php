<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            // [{"degree": "MSc Physics", "institute": "University of the Punjab", "year": 2016}, ...]
            $table->json('education')->nullable()->after('experience_years');
            // Private documents (Laravel "local" disk, never public). Paths only, never full URLs.
            $table->string('cnic_front_path')->nullable()->after('photo_path');
            $table->string('cnic_back_path')->nullable()->after('cnic_front_path');
            $table->string('degree_path')->nullable()->after('cnic_back_path');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->dropColumn(['education', 'cnic_front_path', 'cnic_back_path', 'degree_path']);
        });
    }
};
