<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Backfill usernames for existing users who don't have one.
     * Generates from the email prefix, ensuring uniqueness.
     */
    public function up(): void
    {
        User::whereNull('username')->orWhere('username', '')->orderBy('id')->each(function (User $user) {
            $user->update([
                'username' => User::generateUniqueUsername($user->email),
            ]);
        });
    }

    public function down(): void
    {
        // No rollback — usernames are safe to keep
    }
};
