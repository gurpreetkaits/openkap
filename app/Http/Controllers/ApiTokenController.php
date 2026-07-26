<?php

namespace App\Http\Controllers;

use App\Data\CreateApiTokenData;
use App\Http\Requests\StoreApiTokenRequest;
use App\Managers\ApiTokenManager;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    public function __construct(
        protected ApiTokenManager $apiTokenManager,
    ) {}

    public function index()
    {
        $tokens = $this->apiTokenManager->listForUser(Auth::user());

        return response()->json([
            'tokens' => $tokens
                ->map(fn (PersonalAccessToken $token) => $this->formatToken($token))
                ->values(),
        ]);
    }

    public function store(StoreApiTokenRequest $request)
    {
        // Abilities are fixed to full access server-side (not client-controlled).
        $data = CreateApiTokenData::from([
            'name' => $request->input('name'),
            'expires_in_days' => $request->input('expires_in_days'),
        ]);

        $newToken = $this->apiTokenManager->createForUser(Auth::user(), $data);

        return response()->json([
            'message' => "API token created. Copy it now — you won't be able to see it again.",
            'plain_text_token' => $newToken->plainTextToken,
            'token' => $this->formatToken($newToken->accessToken),
        ], 201);
    }

    public function destroy(int $id)
    {
        $revoked = $this->apiTokenManager->revokeForUser(Auth::user(), $id);

        if (! $revoked) {
            return response()->json(['message' => 'Token not found.'], 404);
        }

        return response()->json(['message' => 'Token revoked.']);
    }

    private function formatToken(PersonalAccessToken $token): array
    {
        return [
            'id' => $token->id,
            'name' => $token->name,
            'abilities' => $token->abilities,
            'last_used_at' => $token->last_used_at?->toISOString(),
            'expires_at' => $token->expires_at?->toISOString(),
            'created_at' => $token->created_at?->toISOString(),
        ];
    }
}
