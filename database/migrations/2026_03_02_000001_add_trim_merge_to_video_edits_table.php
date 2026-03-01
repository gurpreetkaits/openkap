<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_edits', function (Blueprint $table) {
            $table->float('trim_start')->nullable()->after('text_overlays');
            $table->float('trim_end')->nullable()->after('trim_start');
            $table->foreignId('merge_video_id')->nullable()->after('trim_end')
                ->constrained('videos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('video_edits', function (Blueprint $table) {
            $table->dropForeign(['merge_video_id']);
            $table->dropColumn(['trim_start', 'trim_end', 'merge_video_id']);
        });
    }
};
