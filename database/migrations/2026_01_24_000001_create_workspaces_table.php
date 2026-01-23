<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('logo_url', 500)->nullable();

            // Owner
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

            // Subscription (via Polar)
            $table->string('polar_customer_id')->nullable();
            $table->string('polar_subscription_id')->nullable();
            $table->enum('subscription_status', ['free', 'active', 'canceled', 'past_due'])->default('free');
            $table->enum('subscription_plan', ['team', 'team_plus'])->nullable();
            $table->timestamp('subscription_started_at')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
            $table->timestamp('subscription_canceled_at')->nullable();

            // Limits (defaults for Team plan)
            $table->unsignedInteger('max_members')->default(5);
            $table->unsignedInteger('max_storage_gb')->default(100);
            $table->unsignedInteger('max_recording_minutes')->default(60);

            // Usage tracking
            $table->unsignedBigInteger('storage_used_bytes')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
