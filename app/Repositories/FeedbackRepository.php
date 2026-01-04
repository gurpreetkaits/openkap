<?php

namespace App\Repositories;

use App\Models\Feedback;
use Illuminate\Pagination\LengthAwarePaginator;

class FeedbackRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Feedback);
    }

    public function createFeedback(array $data): Feedback
    {
        return Feedback::create($data);
    }

    public function findByUserId(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Feedback::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Feedback
    {
        return Feedback::find($id);
    }
}
