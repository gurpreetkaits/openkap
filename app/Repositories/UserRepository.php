<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new User);
    }

    public function findOrFail(int $id): User
    {
        return User::findOrFail($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Null-safe find: returns null for a null id (preserves the behaviour of
     * the previous inline User::find($nullableId) calls in the managers).
     */
    public function findById(?int $id): ?User
    {
        return $id ? User::find($id) : null;
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();

        return $user;
    }

    public function updateAvatar(User $user, string $avatarPath): User
    {
        $this->deleteAvatarFile($user);

        $user->avatar = $avatarPath;
        $user->save();

        return $user;
    }

    public function deleteAvatar(User $user): User
    {
        $this->deleteAvatarFile($user);

        $user->avatar = null;
        $user->save();

        return $user;
    }

    protected function deleteAvatarFile(User $user): void
    {
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
    }

    /**
     * Reset the user's monthly recording counter if the stored period has expired.
     * Returns true when a reset was performed.
     */
    public function resetMonthlyRecordingPeriodIfDue(User $user): bool
    {
        if (! $user->isMonthlyRecordingPeriodExpired()) {
            return false;
        }

        // Conditional UPDATE so two concurrent callers can't both reset and
        // each then increment their own seconds — only the first wins, the
        // second falls through to `incrementMonthlyRecordingSeconds`.
        $startOfMonth = now()->startOfMonth();
        User::whereKey($user->id)
            ->where(function ($q) use ($startOfMonth) {
                $q->whereNull('monthly_recording_period_start')
                    ->orWhere('monthly_recording_period_start', '<', $startOfMonth);
            })
            ->update([
                'monthly_recording_seconds_used' => 0,
                'monthly_recording_period_start' => $startOfMonth,
            ]);

        $user->refresh();

        return true;
    }

    /**
     * Atomically add seconds to the user's monthly recording counter, rolling
     * the period over in the same statement when it has expired. Single
     * conditional UPDATE → safe under concurrent webhooks for the same user.
     */
    public function incrementMonthlyRecordingSeconds(User $user, int $seconds): void
    {
        if ($seconds <= 0) {
            return;
        }

        $startOfMonth = now()->startOfMonth();

        // CASE-based update: if the stored period is in a prior month (or NULL),
        // reset the counter to $seconds and set period_start to this month;
        // otherwise add $seconds to the existing counter.
        User::whereKey($user->id)->update([
            'monthly_recording_seconds_used' => DB::raw(sprintf(
                'CASE WHEN monthly_recording_period_start IS NULL OR monthly_recording_period_start < %s THEN %d ELSE monthly_recording_seconds_used + %d END',
                DB::connection()->getPdo()->quote($startOfMonth->toDateTimeString()),
                $seconds,
                $seconds,
            )),
            'monthly_recording_period_start' => DB::raw(sprintf(
                'CASE WHEN monthly_recording_period_start IS NULL OR monthly_recording_period_start < %s THEN %s ELSE monthly_recording_period_start END',
                DB::connection()->getPdo()->quote($startOfMonth->toDateTimeString()),
                DB::connection()->getPdo()->quote($startOfMonth->toDateTimeString()),
            )),
        ]);

        $user->refresh();
    }
}
