<?php

namespace App\Managers;

use App\Data\CreateApiTokenData;
use App\Models\User;
use App\Repositories\ApiTokenRepository;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Sanctum\NewAccessToken;

class ApiTokenManager
{
    public function __construct(
        protected ApiTokenRepository $tokens,
    ) {}

    public function listForUser(User $user): Collection
    {
        return $this->tokens->listForUser($user);
    }

    public function createForUser(User $user, CreateApiTokenData $data): NewAccessToken
    {
        return $this->tokens->createForUser(
            $user,
            $data->name,
            $data->abilities,
            $data->expiresAt(),
        );
    }

    public function revokeForUser(User $user, int $tokenId): bool
    {
        return $this->tokens->deleteForUser($user, $tokenId);
    }
}
