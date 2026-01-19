<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Converts user_settings from fixed columns to flexible key-value structure.
     */
    public function up(): void
    {
        // First, backup existing data
        $existingSettings = DB::table('user_settings')->get();

        // Drop the old table
        Schema::dropIfExists('user_settings');

        // Create new key-value structure
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, boolean, integer, float, json
            $table->timestamps();

            // Each user can have only one setting per key
            $table->unique(['user_id', 'key']);

            // Index for faster lookups
            $table->index(['user_id', 'key']);
        });

        // Migrate existing data to new structure
        foreach ($existingSettings as $setting) {
            $settings = [
                [
                    'user_id' => $setting->user_id,
                    'key' => 'auto_zoom_enabled',
                    'value' => $setting->auto_zoom_enabled ? 'true' : 'false',
                    'type' => 'boolean',
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ],
                [
                    'user_id' => $setting->user_id,
                    'key' => 'default_zoom_level',
                    'value' => (string) $setting->default_zoom_level,
                    'type' => 'float',
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ],
                [
                    'user_id' => $setting->user_id,
                    'key' => 'default_zoom_duration_ms',
                    'value' => (string) $setting->default_zoom_duration_ms,
                    'type' => 'integer',
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ],
            ];

            DB::table('user_settings')->insert($settings);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Backup key-value data
        $keyValueSettings = DB::table('user_settings')
            ->select('user_id')
            ->distinct()
            ->get();

        $settingsData = [];
        foreach ($keyValueSettings as $user) {
            $userSettings = DB::table('user_settings')
                ->where('user_id', $user->user_id)
                ->pluck('value', 'key')
                ->toArray();

            $settingsData[] = [
                'user_id' => $user->user_id,
                'auto_zoom_enabled' => ($userSettings['auto_zoom_enabled'] ?? 'true') === 'true',
                'default_zoom_level' => (float) ($userSettings['default_zoom_level'] ?? 2.0),
                'default_zoom_duration_ms' => (int) ($userSettings['default_zoom_duration_ms'] ?? 500),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Drop key-value table
        Schema::dropIfExists('user_settings');

        // Recreate original structure
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->boolean('auto_zoom_enabled')->default(true);
            $table->float('default_zoom_level')->default(2.0);
            $table->integer('default_zoom_duration_ms')->default(500);
            $table->timestamps();
        });

        // Restore data
        if (! empty($settingsData)) {
            DB::table('user_settings')->insert($settingsData);
        }
    }
};
