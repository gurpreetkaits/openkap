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
        if (! Schema::hasColumn('comments', 'parent_id')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->foreignId('parent_id')->nullable()->after('user_id')
                    ->constrained('comments')->onDelete('cascade');
                $table->index('parent_id');
            });
        }

        if (! Schema::hasColumn('comments', 'mentions')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->json('mentions')->nullable()->after('content');
            });
        }

        if (! Schema::hasColumn('comments', 'edited_at')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->timestamp('edited_at')->nullable()->after('updated_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'mentions', 'edited_at']);
        });
    }
};
