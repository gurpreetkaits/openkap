<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extension_errors', function (Blueprint $table) {
            $table->id();

            // Nullable: errors can happen before or after the user is signed in.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('extension_version', 32)->nullable();
            $table->string('browser', 64)->nullable();
            $table->string('platform', 64)->nullable();

            // Where in the extension it blew up: serviceWorker, offscreen, …
            $table->string('context', 32)->index();

            // Stable grouping key, e.g. "ERR_TIMEOUT" or "chunk-upload-failed".
            $table->string('code', 64)->index();

            $table->text('message');
            $table->text('stack')->nullable();

            // Recording session it belongs to, when there is one.
            $table->uuid('session_id')->nullable()->index();

            // Breadcrumbs and arbitrary context captured alongside the error.
            $table->json('detail')->nullable();

            // Client clock — kept separately from created_at so out-of-order
            // or delayed batches stay diagnosable.
            $table->timestamp('occurred_at')->nullable();

            $table->timestamps();

            $table->index(['code', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_errors');
    }
};
