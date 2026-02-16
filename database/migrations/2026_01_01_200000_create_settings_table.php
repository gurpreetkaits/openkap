<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->string('group')->default('general');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('settings')->insert([
            [
                'key' => 'free_video_limit',
                'value' => '5',
                'type' => 'integer',
                'group' => 'subscription',
                'description' => 'Maximum number of videos for free plan users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'free_recording_duration_limit',
                'value' => '300',
                'type' => 'integer',
                'group' => 'subscription',
                'description' => 'Maximum recording duration in seconds for free plan (5 minutes = 300)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'monthly_price',
                'value' => '7',
                'type' => 'integer',
                'group' => 'subscription',
                'description' => 'Monthly subscription price in dollars',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'yearly_price',
                'value' => '80',
                'type' => 'integer',
                'group' => 'subscription',
                'description' => 'Yearly subscription price in dollars',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
