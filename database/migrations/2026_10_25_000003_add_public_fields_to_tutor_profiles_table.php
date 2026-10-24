<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            // Sample tutors shown until launch; removed with `php artisan demo:remove`.
            $table->boolean('is_demo')->default(false)->index()->after('status');
            // Kept up to date from reviews (built later), stored here so search can sort by rating.
            $table->decimal('rating_avg', 2, 1)->nullable()->after('is_demo');
            $table->unsignedInteger('reviews_count')->default(0)->after('rating_avg');
            $table->index(['status', 'hourly_rate_minor']);
        });
    }

    public function down(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->dropIndex(['status', 'hourly_rate_minor']);
            $table->dropIndex(['is_demo']);
            $table->dropColumn(['is_demo', 'rating_avg', 'reviews_count']);
        });
    }
};
