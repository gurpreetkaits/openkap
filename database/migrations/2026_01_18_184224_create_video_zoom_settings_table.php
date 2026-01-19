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
        Schema::create('video_zoom_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->unique()->constrained()->onDelete('cascade');
            $table->boolean('enabled')->default(true);
            $table->float('zoom_level')->default(2.0);
            $table->integer('duration_ms')->default(500);
            $table->json('events')->nullable();
            $table->json('recording_resolution')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_zoom_settings');
    }
};
