<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Google avatar URLs routinely exceed 255 characters, which made the column
 * overflow and abort the sign-in with
 * "SQLSTATE[22001]: Data too long for column 'avatar_url'".
 *
 * The column is never indexed, so widening it costs nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_url', 2048)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_url', 255)->nullable()->change();
        });
    }
};
