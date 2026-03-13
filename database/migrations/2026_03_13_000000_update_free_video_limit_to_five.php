<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update free video limit to 5.
     */
    public function up(): void
    {
        DB::table('settings')
            ->where('key', 'free_video_limit')
            ->update([
                'value' => '5',
                'description' => 'Maximum number of videos for free plan users',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'free_video_limit')
            ->update([
                'value' => '2',
                'description' => 'Maximum number of videos for free plan users',
                'updated_at' => now(),
            ]);
    }
};
