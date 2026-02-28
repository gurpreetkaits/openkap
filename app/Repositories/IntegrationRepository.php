<?php

namespace App\Repositories;

use App\Models\Integration;
use Illuminate\Database\Eloquent\Collection;

class IntegrationRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Integration);
    }

    public function findByUserAndProvider(int $userId, string $provider): ?Integration
    {
        return $this->model->where('user_id', $userId)
            ->where('provider', $provider)
            ->first();
    }

    public function findActiveByUser(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('status', 'active')
            ->get();
    }

    public function findAllByUser(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)->get();
    }

    public function createIntegration(array $data): Integration
    {
        return $this->model->create($data);
    }

    public function updateTokens(Integration $integration, string $accessToken, ?string $refreshToken, ?\DateTimeInterface $expiresAt): bool
    {
        return $integration->update([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken ?? $integration->refresh_token,
            'token_expires_at' => $expiresAt,
            'status' => 'active',
        ]);
    }

    public function deleteByUserAndProvider(int $userId, string $provider): bool
    {
        $integration = $this->findByUserAndProvider($userId, $provider);

        if (! $integration) {
            return false;
        }

        return $this->delete($integration);
    }
}
