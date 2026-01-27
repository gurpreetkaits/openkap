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
            $table->string('blur_status')->nullable()->after('storage_type');
            $table->integer('blur_progress')->default(0)->after('blur_status');
            $table->text('blur_error')->nullable()->after('blur_progress');
            $table->json('blur_region')->nullable()->after('blur_error');
            $table->decimal('blur_start_time', 10, 2)->nullable()->after('blur_region');
            $table->decimal('blur_end_time', 10, 2)->nullable()->after('blur_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn([
                'blur_status',
                'blur_progress',
                'blur_error',
                'blur_region',
                'blur_start_time',
                'blur_end_time',
            ]);
        });
    }
};
