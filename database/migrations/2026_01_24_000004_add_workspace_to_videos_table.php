<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('workspace_id')
                ->nullable()
                ->after('user_id')
                ->constrained('workspaces')
                ->nullOnDelete();

            // Add file size tracking if not exists
            if (! Schema::hasColumn('videos', 'file_size_bytes')) {
                $table->unsignedBigInteger('file_size_bytes')->default(0)->after('duration');
            }

            $table->index('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropIndex(['workspace_id']);
            $table->dropColumn('workspace_id');

            if (Schema::hasColumn('videos', 'file_size_bytes')) {
                $table->dropColumn('file_size_bytes');
            }
        });
    }
};
