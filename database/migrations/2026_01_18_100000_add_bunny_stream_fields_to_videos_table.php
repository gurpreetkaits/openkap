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
            // Bunny Stream video reference
            $table->string('bunny_video_id')->nullable()->after('user_id');
            $table->string('bunny_library_id')->nullable()->after('bunny_video_id');

            // Bunny Stream status tracking
            // Status: pending, uploading, processing, transcoding, ready, error
            $table->string('bunny_status')->default('pending')->after('bunny_library_id');
            $table->text('bunny_error')->nullable()->after('bunny_status');

            // Video metadata from Bunny
            $table->string('bunny_resolution')->nullable()->after('bunny_error');
            $table->bigInteger('bunny_file_size')->nullable()->after('bunny_resolution');

            // Storage type: 'local' or 'bunny'
            $table->string('storage_type')->default('local')->after('bunny_file_size');

            // Index for faster lookups
            $table->index('bunny_video_id');
            $table->index('bunny_status');
            $table->index('storage_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['bunny_video_id']);
            $table->dropIndex(['bunny_status']);
            $table->dropIndex(['storage_type']);

            $table->dropColumn([
                'bunny_video_id',
                'bunny_library_id',
                'bunny_status',
                'bunny_error',
                'bunny_resolution',
                'bunny_file_size',
                'storage_type',
            ]);
        });
    }
};
