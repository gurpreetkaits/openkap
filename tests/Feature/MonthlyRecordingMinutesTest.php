<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\Video;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonthlyRecordingMinutesTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // PLAN LIMITS
    // ==========================================

    #[Test]
    public function free_user_has_free_monthly_minutes_limit(): void
    {
        $user = User::factory()->free()->create();

        $this->assertSame(25, $user->getMonthlyRecordingMinutesLimit());
    }

    #[Test]
    public function pro_user_has_pro_monthly_minutes_limit(): void
    {
        $user = User::factory()->withProSubscription()->create();

        $this->assertSame(500, $user->getMonthlyRecordingMinutesLimit());
    }

    #[Test]
    public function admin_can_override_limits_via_settings(): void
    {
        Setting::setValue('free_monthly_recording_minutes_limit', 10, 'integer');
        Setting::setValue('pro_monthly_recording_minutes_limit', 1000, 'integer');

        $free = User::factory()->free()->create();
        $pro = User::factory()->withProSubscription()->create();

        $this->assertSame(10, $free->getMonthlyRecordingMinutesLimit());
        $this->assertSame(1000, $pro->getMonthlyRecordingMinutesLimit());
    }

    // ==========================================
    // PERIOD RESET
    // ==========================================

    #[Test]
    public function period_is_expired_when_never_set(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->isMonthlyRecordingPeriodExpired());
    }

    #[Test]
    public function period_is_expired_when_stored_period_is_in_a_previous_month(): void
    {
        $user = User::factory()->create([
            'monthly_recording_seconds_used' => 600,
            'monthly_recording_period_start' => Carbon::now()->subMonth()->startOfMonth(),
        ]);

        $this->assertTrue($user->isMonthlyRecordingPeriodExpired());
        $this->assertSame(0, $user->getMonthlyRecordingMinutesUsed());
    }

    #[Test]
    public function period_is_current_when_stored_period_is_start_of_this_month(): void
    {
        $user = User::factory()->create([
            'monthly_recording_seconds_used' => 600,
            'monthly_recording_period_start' => Carbon::now()->startOfMonth(),
        ]);

        $this->assertFalse($user->isMonthlyRecordingPeriodExpired());
        // 600 seconds = 10 minutes
        $this->assertSame(10, $user->getMonthlyRecordingMinutesUsed());
    }

    #[Test]
    public function reset_zeroes_counter_and_sets_period_to_start_of_month(): void
    {
        $user = User::factory()->create([
            'monthly_recording_seconds_used' => 9999,
            'monthly_recording_period_start' => Carbon::now()->subMonths(2)->startOfMonth(),
        ]);

        $reset = app(UserRepository::class)->resetMonthlyRecordingPeriodIfDue($user);

        $this->assertTrue($reset);
        $user->refresh();
        $this->assertSame(0, $user->monthly_recording_seconds_used);
        $this->assertTrue($user->monthly_recording_period_start->equalTo(Carbon::now()->startOfMonth()));
    }

    #[Test]
    public function reset_is_noop_when_period_is_current(): void
    {
        $user = User::factory()->create([
            'monthly_recording_seconds_used' => 120,
            'monthly_recording_period_start' => Carbon::now()->startOfMonth(),
        ]);

        $reset = app(UserRepository::class)->resetMonthlyRecordingPeriodIfDue($user);

        $this->assertFalse($reset);
        $user->refresh();
        $this->assertSame(120, $user->monthly_recording_seconds_used);
    }

    // ==========================================
    // INCREMENT
    // ==========================================

    #[Test]
    public function increment_adds_seconds_to_counter(): void
    {
        $user = User::factory()->create([
            'monthly_recording_seconds_used' => 60,
            'monthly_recording_period_start' => Carbon::now()->startOfMonth(),
        ]);

        app(UserRepository::class)->incrementMonthlyRecordingSeconds($user, 90);

        $user->refresh();
        $this->assertSame(150, $user->monthly_recording_seconds_used);
    }

    #[Test]
    public function increment_resets_first_when_period_expired(): void
    {
        $user = User::factory()->create([
            'monthly_recording_seconds_used' => 9999,
            'monthly_recording_period_start' => Carbon::now()->subMonth()->startOfMonth(),
        ]);

        app(UserRepository::class)->incrementMonthlyRecordingSeconds($user, 30);

        $user->refresh();
        $this->assertSame(30, $user->monthly_recording_seconds_used);
    }

    #[Test]
    public function increment_is_noop_for_zero_or_negative_seconds(): void
    {
        $user = User::factory()->create([
            'monthly_recording_seconds_used' => 60,
            'monthly_recording_period_start' => Carbon::now()->startOfMonth(),
        ]);

        app(UserRepository::class)->incrementMonthlyRecordingSeconds($user, 0);
        app(UserRepository::class)->incrementMonthlyRecordingSeconds($user, -10);

        $user->refresh();
        $this->assertSame(60, $user->monthly_recording_seconds_used);
    }

    // ==========================================
    // ENFORCEMENT (canRecordVideo)
    // ==========================================

    #[Test]
    public function pro_user_cannot_record_when_monthly_minutes_exhausted(): void
    {
        $user = User::factory()->withProSubscription()->create([
            // 500 minutes = 30000 seconds at the Pro cap
            'monthly_recording_seconds_used' => 30000,
            'monthly_recording_period_start' => Carbon::now()->startOfMonth(),
        ]);

        $this->assertTrue($user->hasExceededMonthlyRecordingMinutes());
        $this->assertFalse($user->canRecordVideo());
    }

    #[Test]
    public function free_user_blocked_by_minutes_returns_specific_error(): void
    {
        $user = User::factory()->free()->create([
            'monthly_recording_seconds_used' => 25 * 60,
            'monthly_recording_period_start' => Carbon::now()->startOfMonth(),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/bunny/videos/create', [
                'title' => 'Test',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'monthly_minutes_limit_reached',
            ])
            ->assertJsonPath('monthly_recording_minutes_used', 25)
            ->assertJsonPath('monthly_recording_minutes_limit', 25)
            ->assertJsonPath('remaining_monthly_recording_minutes', 0);
    }

    // ==========================================
    // WEBHOOK INCREMENT
    // ==========================================

    #[Test]
    public function bunny_webhook_increments_minutes_on_ready(): void
    {
        $user = User::factory()->withProSubscription()->create([
            'monthly_recording_seconds_used' => 0,
            'monthly_recording_period_start' => Carbon::now()->startOfMonth(),
        ]);

        $video = Video::factory()->bunnyProcessing()->create([
            'user_id' => $user->id,
            'bunny_video_id' => 'ready-guid',
            'duration' => 0,
        ]);

        $this->postJson('/api/webhooks/bunny', [
            'VideoGuid' => 'ready-guid',
            'Status' => 4,
            'Length' => 180, // 3 minutes
            'Width' => 1280,
            'Height' => 720,
        ])->assertStatus(200);

        $user->refresh();
        $this->assertSame(180, $user->monthly_recording_seconds_used);
        $this->assertSame(3, $user->getMonthlyRecordingMinutesUsed());
    }

    #[Test]
    public function bunny_webhook_does_not_double_count_on_repeat_ready(): void
    {
        $user = User::factory()->withProSubscription()->create([
            'monthly_recording_seconds_used' => 0,
            'monthly_recording_period_start' => Carbon::now()->startOfMonth(),
        ]);

        $video = Video::factory()->bunnyProcessing()->create([
            'user_id' => $user->id,
            'bunny_video_id' => 'ready-guid',
            'duration' => 0,
        ]);

        $payload = [
            'VideoGuid' => 'ready-guid',
            'Status' => 4,
            'Length' => 120,
            'Width' => 1280,
            'Height' => 720,
        ];

        $this->postJson('/api/webhooks/bunny', $payload)->assertStatus(200);
        $this->postJson('/api/webhooks/bunny', $payload)->assertStatus(200);

        $user->refresh();
        // Second webhook is short-circuited because video is already `ready`.
        $this->assertSame(120, $user->monthly_recording_seconds_used);
    }

    #[Test]
    public function increment_rolls_period_atomically_in_one_statement(): void
    {
        // Stored period is in a prior month — should reset to start of this
        // month AND apply the increment in a single UPDATE.
        $user = User::factory()->create([
            'monthly_recording_seconds_used' => 9999,
            'monthly_recording_period_start' => Carbon::now()->subMonths(3)->startOfMonth(),
        ]);

        app(UserRepository::class)->incrementMonthlyRecordingSeconds($user, 90);

        $user->refresh();
        $this->assertSame(90, $user->monthly_recording_seconds_used);
        $this->assertTrue(
            $user->monthly_recording_period_start->equalTo(Carbon::now()->startOfMonth()),
            'period_start should advance to start of current month'
        );
    }

    // ==========================================
    // SUBSCRIPTION API SURFACE
    // ==========================================

    #[Test]
    public function subscription_status_exposes_minutes_fields(): void
    {
        $user = User::factory()->withProSubscription()->create([
            'monthly_recording_seconds_used' => 240, // 4 minutes
            'monthly_recording_period_start' => Carbon::now()->startOfMonth(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/subscription/status');

        $response->assertOk()
            ->assertJsonPath('subscription.monthly_recording_minutes_used', 4)
            ->assertJsonPath('subscription.monthly_recording_minutes_limit', 500)
            ->assertJsonPath('subscription.remaining_monthly_recording_minutes', 496);
    }
}
