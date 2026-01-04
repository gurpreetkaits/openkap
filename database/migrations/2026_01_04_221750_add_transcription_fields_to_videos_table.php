<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // Transcription fields
            $table->string('transcription_status')->default('pending')->after('hls_converted_at');
            $table->longText('transcription')->nullable()->after('transcription_status');
            $table->text('transcription_error')->nullable()->after('transcription');
            $table->integer('transcription_progress')->default(0)->after('transcription_error');
            $table->timestamp('transcription_generated_at')->nullable()->after('transcription_progress');

            // Summary fields
            $table->string('summary_status')->default('pending')->after('transcription_generated_at');
            $table->longText('summary')->nullable()->after('summary_status');
            $table->text('summary_error')->nullable()->after('summary');
            $table->timestamp('summary_generated_at')->nullable()->after('summary_error');

            // Indexes for filtering
            $table->index('transcription_status');
            $table->index('summary_status');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropIndex(['transcription_status']);
            $table->dropIndex(['summary_status']);

            $table->dropColumn([
                'transcription_status',
                'transcription',
                'transcription_error',
                'transcription_progress',
                'transcription_generated_at',
                'summary_status',
                'summary',
                'summary_error',
                'summary_generated_at',
            ]);
        });
    }
};
