<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('camera_conversion_status')->default('pending')->after('has_camera');
            $table->unsignedTinyInteger('camera_conversion_progress')->default(0)->after('camera_conversion_status');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['camera_conversion_status', 'camera_conversion_progress']);
        });
    }
};
