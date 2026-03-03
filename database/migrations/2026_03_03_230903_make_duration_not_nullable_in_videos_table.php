<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Delete any videos with 0 or null duration (orphans from failed uploads)
        DB::table('videos')->whereNull('duration')->orWhere('duration', 0)->delete();

        Schema::table('videos', function (Blueprint $table) {
            $table->integer('duration')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->integer('duration')->nullable()->change();
        });
    }
};
