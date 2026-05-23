<?php

namespace App\Repositories;

use App\Models\SupportConversation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SupportConversationRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new SupportConversation);
    }

    public function findForUser(User $user): ?SupportConversation
    {
        return SupportConversation::query()->where('user_id', $user->id)->first();
    }

    public function firstOrCreateForUser(User $user): SupportConversation
    {
        return SupportConversation::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'open']
        );
    }

    public function touchLastMessageAt(SupportConversation $conversation): SupportConversation
    {
        $conversation->update(['last_message_at' => now()]);

        return $conversation->refresh();
    }

    public function markReadByAdmin(SupportConversation $conversation): SupportConversation
    {
        $conversation->update(['last_admin_read_at' => now()]);

        return $conversation->refresh();
    }

    public function markReadByUser(SupportConversation $conversation): SupportConversation
    {
        $conversation->update(['last_user_read_at' => now()]);

        return $conversation->refresh();
    }

    public function paginateForAdmin(int $perPage = 25, ?string $search = null): LengthAwarePaginator
    {
        return SupportConversation::query()
            ->with(['user:id,name,email,avatar_url', 'latestMessage'])
            ->when($search, function (Builder $query, string $term) {
                $query->whereHas('user', function (Builder $sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderByRaw('last_message_at IS NULL, last_message_at DESC')
            ->paginate($perPage);
    }
}
