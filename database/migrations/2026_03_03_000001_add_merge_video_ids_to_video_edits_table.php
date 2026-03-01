<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_edits', function (Blueprint $table) {
            $table->json('merge_video_ids')->nullable()->after('merge_position');
            $table->integer('main_video_position')->default(0)->after('merge_video_ids');
        });
    }

    public function down(): void
    {
        Schema::table('video_edits', function (Blueprint $table) {
            $table->dropColumn(['merge_video_ids', 'main_video_position']);
        });
    }
};
