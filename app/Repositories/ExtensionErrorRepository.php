<?php

namespace App\Repositories;

use App\Models\ExtensionError;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ExtensionErrorRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new ExtensionError);
    }

    public function createError(array $data): ExtensionError
    {
        return ExtensionError::create($data);
    }

    public function insertMany(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        ExtensionError::insert($rows);

        return count($rows);
    }

    public function recent(int $limit = 100): Collection
    {
        return ExtensionError::query()
            ->with('user:id,email,name')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Error codes ordered by how often they fired, for triage.
     */
    public function groupedByCode(int $sinceDays = 7, int $limit = 50): SupportCollection
    {
        return ExtensionError::query()
            ->selectRaw('code, context, COUNT(*) as occurrences, COUNT(DISTINCT user_id) as affected_users, MAX(created_at) as last_seen_at')
            ->where('created_at', '>=', now()->subDays($sinceDays))
            ->groupBy('code', 'context')
            ->orderByDesc('occurrences')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'code' => $row->code,
                'context' => $row->context,
                'occurrences' => (int) $row->occurrences,
                'affected_users' => (int) $row->affected_users,
                'last_seen_at' => $row->last_seen_at,
            ]);
    }

    public function pruneOlderThan(int $days): int
    {
        return ExtensionError::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}
