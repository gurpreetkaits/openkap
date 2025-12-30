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
            $table->string('hls_status')->default('pending')->after('conversion_status');
            $table->unsignedTinyInteger('hls_progress')->default(0)->after('hls_status');
            $table->string('hls_path')->nullable()->after('hls_progress');
            $table->text('hls_error')->nullable()->after('hls_path');
            $table->timestamp('hls_converted_at')->nullable()->after('hls_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['hls_status', 'hls_progress', 'hls_path', 'hls_error', 'hls_converted_at']);
        });
    }
};
