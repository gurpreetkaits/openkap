<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('bug_detection_status')->default('pending')->after('summary_generated_at');
            $table->json('detected_bugs')->nullable()->after('bug_detection_status');
            $table->text('bug_detection_error')->nullable()->after('detected_bugs');
            $table->timestamp('bug_detection_generated_at')->nullable()->after('bug_detection_error');

            $table->index('bug_detection_status');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['bug_detection_status']);

            $table->dropColumn([
                'bug_detection_status',
                'detected_bugs',
                'bug_detection_error',
                'bug_detection_generated_at',
            ]);
        });
    }
};
