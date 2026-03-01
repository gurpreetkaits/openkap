<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_edits', function (Blueprint $table) {
            $table->string('merge_position', 10)->default('after')->after('merge_video_id');
        });
    }

    public function down(): void
    {
        Schema::table('video_edits', function (Blueprint $table) {
            $table->dropColumn('merge_position');
        });
    }
};
