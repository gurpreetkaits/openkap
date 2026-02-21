<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_edits', function (Blueprint $table) {
            $table->json('text_overlays')->nullable()->after('overlay_configs');
        });
    }

    public function down(): void
    {
        Schema::table('video_edits', function (Blueprint $table) {
            $table->dropColumn('text_overlays');
        });
    }
};
