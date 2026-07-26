<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function user_can_create_an_api_token(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/tokens', ['name' => 'qa-record']);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'plain_text_token', 'token' => ['id', 'name', 'expires_at', 'created_at']])
            ->assertJsonPath('token.name', 'qa-record');

        // Plaintext token is returned exactly once and looks like a Sanctum token.
        $this->assertStringContainsString('|', $response->json('plain_text_token'));

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'tokenable_type' => User::class,
            'name' => 'qa-record',
        ]);
    }

    #[Test]
    public function a_token_defaults_to_no_expiry_but_honours_expires_in_days(): void
    {
        $noExpiry = $this->actingAs($this->user)
            ->postJson('/api/tokens', ['name' => 'ci-forever']);
        $noExpiry->assertStatus(201)->assertJsonPath('token.expires_at', null);

        $withExpiry = $this->actingAs($this->user)
            ->postJson('/api/tokens', ['name' => 'temp', 'expires_in_days' => 30]);
        $withExpiry->assertStatus(201);
        $this->assertNotNull($withExpiry->json('token.expires_at'));
    }

    #[Test]
    public function listing_returns_api_tokens_but_hides_session_tokens(): void
    {
        // Simulate a login/session token (created by GoogleAuthController) + a real API token.
        $this->user->createToken('google-auth');
        $this->user->createToken('ci');

        $response = $this->actingAs($this->user)->getJson('/api/tokens');

        $response->assertOk();
        $names = collect($response->json('tokens'))->pluck('name');
        $this->assertTrue($names->contains('ci'));
        $this->assertFalse($names->contains('google-auth'));
        $this->assertCount(1, $names);
    }

    #[Test]
    public function the_token_hash_is_never_exposed(): void
    {
        $this->user->createToken('ci');

        $response = $this->actingAs($this->user)->getJson('/api/tokens');

        $response->assertOk();
        $this->assertArrayNotHasKey('token', $response->json('tokens.0'));
    }

    #[Test]
    public function user_can_revoke_their_own_token(): void
    {
        $id = $this->user->createToken('ci')->accessToken->id;

        $response = $this->actingAs($this->user)->deleteJson("/api/tokens/{$id}");

        $response->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $id]);
    }

    #[Test]
    public function user_cannot_revoke_another_users_token(): void
    {
        $other = User::factory()->create();
        $id = $other->createToken('theirs')->accessToken->id;

        $response = $this->actingAs($this->user)->deleteJson("/api/tokens/{$id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $id]);
    }

    #[Test]
    public function creating_a_token_requires_a_name(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/tokens', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function the_token_endpoints_require_authentication(): void
    {
        $this->getJson('/api/tokens')->assertStatus(401);
        $this->postJson('/api/tokens', ['name' => 'x'])->assertStatus(401);
        $this->deleteJson('/api/tokens/1')->assertStatus(401);
    }
}
