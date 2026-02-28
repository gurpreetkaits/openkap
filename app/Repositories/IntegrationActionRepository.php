<?php

namespace App\Repositories;

use App\Models\IntegrationAction;
use Illuminate\Database\Eloquent\Collection;

class IntegrationActionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new IntegrationAction);
    }

    public function createAction(array $data): IntegrationAction
    {
        return $this->model->create($data);
    }

    public function updateAction(IntegrationAction $action, array $data): bool
    {
        return $this->update($action, $data);
    }

    public function findByVideoId(int $videoId): Collection
    {
        return $this->model->where('video_id', $videoId)
            ->with('integration:id,provider')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByVideoAndUser(int $videoId, int $userId): Collection
    {
        return $this->model->where('video_id', $videoId)
            ->where('user_id', $userId)
            ->with('integration:id,provider')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
