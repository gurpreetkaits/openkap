<?php

namespace App\Repositories;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenRepository extends BaseRepository
{
    /** Name used for transient login/session tokens — hidden from the API-token UI. */
    private const SESSION_TOKEN_NAME = 'google-auth';

    public function __construct()
    {
        parent::__construct(new PersonalAccessToken);
    }

    /** Personal API tokens the user created (excludes login/session tokens). */
    public function listForUser(User $user): Collection
    {
        return $user->tokens()
            ->where('name', '!=', self::SESSION_TOKEN_NAME)
            ->latest()
            ->get();
    }

    public function createForUser(User $user, string $name, array $abilities, ?DateTimeInterface $expiresAt): NewAccessToken
    {
        return $user->createToken($name, $abilities, $expiresAt);
    }

    /** Find one of the user's own API tokens (never a session token). */
    public function findForUser(User $user, int $id): ?PersonalAccessToken
    {
        return $user->tokens()
            ->where('name', '!=', self::SESSION_TOKEN_NAME)
            ->whereKey($id)
            ->first();
    }

    public function deleteForUser(User $user, int $id): bool
    {
        $token = $this->findForUser($user, $id);

        return $token ? (bool) $token->delete() : false;
    }
}
