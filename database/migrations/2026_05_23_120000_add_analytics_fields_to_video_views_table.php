<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_views', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->after('user_agent');
            $table->string('country', 80)->nullable()->after('country_code');
            $table->string('device_type', 16)->nullable()->after('country');
            $table->string('browser', 32)->nullable()->after('device_type');
            $table->string('os', 32)->nullable()->after('browser');
            $table->string('referrer_source', 24)->nullable()->after('os');
            $table->string('referrer_url', 500)->nullable()->after('referrer_source');
            $table->string('session_id', 64)->nullable()->after('referrer_url');
            $table->integer('progress_max_seconds')->default(0)->after('watch_duration');

            $table->index(['video_id', 'country_code']);
            $table->index(['video_id', 'device_type']);
            $table->index(['video_id', 'referrer_source']);
            $table->index(['session_id']);
        });
    }

    public function down(): void
    {
        Schema::table('video_views', function (Blueprint $table) {
            $table->dropIndex(['video_id', 'country_code']);
            $table->dropIndex(['video_id', 'device_type']);
            $table->dropIndex(['video_id', 'referrer_source']);
            $table->dropIndex(['session_id']);
            $table->dropColumn([
                'country_code',
                'country',
                'device_type',
                'browser',
                'os',
                'referrer_source',
                'referrer_url',
                'session_id',
                'progress_max_seconds',
            ]);
        });
    }
};
