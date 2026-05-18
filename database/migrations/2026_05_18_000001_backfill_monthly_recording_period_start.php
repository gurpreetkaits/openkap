<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Anchor the recording-minutes counter to the start of the current
        // calendar month for every existing user. New uploads from now on
        // will increment this period; pre-existing in-month uploads are not
        // retroactively charged (deliberate: avoids penalising users when
        // the cap is introduced).
        DB::table('users')
            ->whereNull('monthly_recording_period_start')
            ->update([
                'monthly_recording_seconds_used' => 0,
                'monthly_recording_period_start' => now()->startOfMonth(),
            ]);
    }

    public function down(): void
    {
        // Best-effort reverse: clear the anchor so the column matches its
        // pre-migration shape. Counter is intentionally left as-is since
        // post-migration uploads may have incremented it.
        DB::table('users')->update([
            'monthly_recording_period_start' => null,
        ]);
    }
};
