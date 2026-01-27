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
        Schema::create('screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('folder_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('workspace_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('share_token', 64)->unique();
            $table->boolean('is_public')->default(true);
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('share_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screenshots');
    }
};
