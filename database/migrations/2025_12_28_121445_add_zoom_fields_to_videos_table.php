<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->json('click_events')->nullable()->after('duration');
            $table->string('processing_status')->default('ready')->after('click_events'); // ready, processing, processed
            $table->string('processed_video_path')->nullable()->after('processing_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['click_events', 'processing_status', 'processed_video_path']);
        });
    }
};
