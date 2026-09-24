<?php

namespace Tests\Feature;

use App\Models\ExtensionError;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The extension reports its own failures here. Everything that happens in a
 * service worker or offscreen document is otherwise invisible: nobody has a
 * devtools window open on them when a recording breaks.
 */
class ExtensionErrorReportingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_accepts_a_batch_of_reports_from_a_signed_in_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/extension/errors', [
                'errors' => [
                    [
                        'context' => 'offscreen',
                        'code' => 'ERR_TIMEOUT',
                        'message' => 'Upload timed out after 30s',
                        'session_id' => '11111111-2222-3333-4444-555555555555',
                        'extension_version' => '1.2.0',
                        'browser' => 'Chrome/140',
                        'platform' => 'macOS',
                        'detail' => ['chunk_index' => 17, 'attempts' => 3],
                        'occurred_at' => '2026-09-24T10:00:00Z',
                    ],
                    [
                        'context' => 'serviceWorker',
                        'code' => 'ERR_NETWORK',
                        'message' => 'Connection lost',
                    ],
                ],
            ])
            ->assertStatus(202)
            ->assertJsonPath('stored', 2);

        $this->assertSame(2, ExtensionError::count());

        $timeout = ExtensionError::where('code', 'ERR_TIMEOUT')->first();

        $this->assertSame($user->id, $timeout->user_id);
        $this->assertSame('offscreen', $timeout->context);
        $this->assertSame(17, $timeout->detail['chunk_index']);
        $this->assertSame('11111111-2222-3333-4444-555555555555', $timeout->session_id);
        $this->assertSame('2026-09-24 10:00:00', $timeout->occurred_at->toDateTimeString());
    }

    #[Test]
    public function it_accepts_reports_from_a_signed_out_extension(): void
    {
        // A failure in the sign-in or startup path is exactly the report we
        // most need and cannot collect behind auth.
        $this->postJson('/api/extension/errors', [
            'errors' => [[
                'context' => 'serviceWorker',
                'code' => 'ERR_AUTH',
                'message' => 'Token refresh failed',
            ]],
        ])->assertStatus(202);

        $this->assertNull(ExtensionError::first()->user_id);
    }

    #[Test]
    public function it_rejects_an_empty_or_oversized_batch(): void
    {
        $this->postJson('/api/extension/errors', ['errors' => []])
            ->assertStatus(422);

        $this->postJson('/api/extension/errors', [
            'errors' => array_fill(0, 100, [
                'context' => 'offscreen',
                'code' => 'ERR_X',
                'message' => 'spam',
            ]),
        ])->assertStatus(422);
    }

    #[Test]
    public function it_ignores_a_malformed_session_id_rather_than_storing_it(): void
    {
        $this->postJson('/api/extension/errors', [
            'errors' => [[
                'context' => 'offscreen',
                'code' => 'ERR_X',
                'message' => 'bad session',
                'session_id' => '../../etc/passwd',
            ]],
        ])->assertStatus(202);

        $this->assertNull(ExtensionError::first()->session_id);
    }

    #[Test]
    public function long_fields_are_truncated_instead_of_failing(): void
    {
        $this->postJson('/api/extension/errors', [
            'errors' => [[
                'context' => 'offscreen',
                'code' => 'ERR_X',
                'message' => str_repeat('a', 2000),
                'stack' => str_repeat('b', 8000),
            ]],
        ])->assertStatus(202);

        $error = ExtensionError::first();

        $this->assertSame(2000, strlen($error->message));
        $this->assertSame(8000, strlen($error->stack));
    }

    #[Test]
    public function admins_can_triage_what_is_failing(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $reporter = User::factory()->create();

        $this->actingAs($reporter)->postJson('/api/extension/errors', [
            'errors' => [
                ['context' => 'offscreen', 'code' => 'ERR_TIMEOUT', 'message' => 'one'],
                ['context' => 'offscreen', 'code' => 'ERR_TIMEOUT', 'message' => 'two'],
                ['context' => 'serviceWorker', 'code' => 'ERR_NETWORK', 'message' => 'three'],
            ],
        ])->assertStatus(202);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/extension-errors')
            ->assertOk();

        $byCode = collect($response->json('data.by_code'));

        $this->assertSame('ERR_TIMEOUT', $byCode->first()['code']);
        $this->assertSame(2, $byCode->first()['occurrences']);
        $this->assertSame(1, $byCode->first()['affected_users']);
        $this->assertCount(3, $response->json('data.recent'));
    }

    #[Test]
    public function non_admins_cannot_read_the_triage_report(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/admin/extension-errors')
            ->assertForbidden();
    }
}
