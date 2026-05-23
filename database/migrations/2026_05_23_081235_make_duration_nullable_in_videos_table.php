<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Duration is now extracted server-side via ffprobe AFTER the upload
     * lands, so the column has to allow NULL between the INSERT and the
     * probe completing. The Bunny webhook / RemuxWebmJob backfill it.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->integer('duration')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->integer('duration')->nullable(false)->change();
        });
    }
};
