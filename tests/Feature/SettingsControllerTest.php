<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // ==========================================
    // READ SETTINGS TESTS
    // ==========================================

    public function test_user_can_get_settings_with_defaults(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/settings');

        $response->assertOk()
            ->assertJsonStructure([
                'settings' => [
                    'auto_zoom_enabled',
                    'default_zoom_level',
                    'default_zoom_duration_ms',
                ],
            ])
            ->assertJson([
                'settings' => [
                    'auto_zoom_enabled' => true,
                    'default_zoom_level' => 2.0,
                    'default_zoom_duration_ms' => 500,
                ],
            ]);
    }

    public function test_user_can_get_existing_settings(): void
    {
        // Create existing settings for the user
        UserSetting::create([
            'user_id' => $this->user->id,
            'key' => 'auto_zoom_enabled',
            'value' => 'false',
            'type' => 'boolean',
        ]);
        UserSetting::create([
            'user_id' => $this->user->id,
            'key' => 'default_zoom_level',
            'value' => '3.5',
            'type' => 'float',
        ]);
        UserSetting::create([
            'user_id' => $this->user->id,
            'key' => 'default_zoom_duration_ms',
            'value' => '1000',
            'type' => 'integer',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/user/settings');

        $response->assertOk()
            ->assertJson([
                'settings' => [
                    'auto_zoom_enabled' => false,
                    'default_zoom_level' => 3.5,
                    'default_zoom_duration_ms' => 1000,
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_get_settings(): void
    {
        $response = $this->getJson('/api/user/settings');

        $response->assertStatus(401);
    }

    // ==========================================
    // UPDATE SETTINGS TESTS
    // ==========================================

    public function test_user_can_update_all_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'auto_zoom_enabled' => false,
                'default_zoom_level' => 2.5,
                'default_zoom_duration_ms' => 800,
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Settings updated successfully',
                'settings' => [
                    'auto_zoom_enabled' => false,
                    'default_zoom_level' => 2.5,
                    'default_zoom_duration_ms' => 800,
                ],
            ]);

        // Verify settings are persisted in database
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $this->user->id,
            'key' => 'auto_zoom_enabled',
            'value' => 'false',
        ]);
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $this->user->id,
            'key' => 'default_zoom_level',
            'value' => '2.5',
        ]);
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $this->user->id,
            'key' => 'default_zoom_duration_ms',
            'value' => '800',
        ]);
    }

    public function test_user_can_update_single_setting(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'auto_zoom_enabled' => false,
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Settings updated successfully',
                'settings' => [
                    'auto_zoom_enabled' => false,
                    // Other settings should have defaults
                    'default_zoom_level' => 2.0,
                    'default_zoom_duration_ms' => 500,
                ],
            ]);
    }

    public function test_user_can_enable_auto_zoom(): void
    {
        // First disable it
        UserSetting::create([
            'user_id' => $this->user->id,
            'key' => 'auto_zoom_enabled',
            'value' => 'false',
            'type' => 'boolean',
        ]);

        // Then enable it
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'auto_zoom_enabled' => true,
            ]);

        $response->assertOk()
            ->assertJson([
                'settings' => [
                    'auto_zoom_enabled' => true,
                ],
            ]);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $this->user->id,
            'key' => 'auto_zoom_enabled',
            'value' => 'true',
        ]);
    }

    public function test_user_can_disable_auto_zoom(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'auto_zoom_enabled' => false,
            ]);

        $response->assertOk()
            ->assertJson([
                'settings' => [
                    'auto_zoom_enabled' => false,
                ],
            ]);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $this->user->id,
            'key' => 'auto_zoom_enabled',
            'value' => 'false',
        ]);
    }

    public function test_user_can_update_zoom_level(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'default_zoom_level' => 3.0,
            ]);

        $response->assertOk()
            ->assertJson([
                'settings' => [
                    'default_zoom_level' => 3.0,
                ],
            ]);
    }

    public function test_user_can_update_zoom_duration(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'default_zoom_duration_ms' => 1500,
            ]);

        $response->assertOk()
            ->assertJson([
                'settings' => [
                    'default_zoom_duration_ms' => 1500,
                ],
            ]);
    }

    public function test_zoom_level_below_minimum_fails_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'default_zoom_level' => 0.5, // Below minimum of 1.2
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['default_zoom_level']);
    }

    public function test_zoom_level_above_maximum_fails_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'default_zoom_level' => 10.0, // Above maximum of 4.0
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['default_zoom_level']);
    }

    public function test_zoom_duration_below_minimum_fails_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'default_zoom_duration_ms' => 50, // Below minimum of 100
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['default_zoom_duration_ms']);
    }

    public function test_zoom_duration_above_maximum_fails_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'default_zoom_duration_ms' => 5000, // Above maximum of 2000
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['default_zoom_duration_ms']);
    }

    public function test_zoom_level_at_minimum_boundary_is_accepted(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'default_zoom_level' => 1.2,
            ]);

        $response->assertOk()
            ->assertJson([
                'settings' => [
                    'default_zoom_level' => 1.2,
                ],
            ]);
    }

    public function test_zoom_level_at_maximum_boundary_is_accepted(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'default_zoom_level' => 4.0,
            ]);

        $response->assertOk()
            ->assertJson([
                'settings' => [
                    'default_zoom_level' => 4.0,
                ],
            ]);
    }

    public function test_zoom_duration_at_minimum_boundary_is_accepted(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'default_zoom_duration_ms' => 100,
            ]);

        $response->assertOk()
            ->assertJson([
                'settings' => [
                    'default_zoom_duration_ms' => 100,
                ],
            ]);
    }

    public function test_zoom_duration_at_maximum_boundary_is_accepted(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'default_zoom_duration_ms' => 2000,
            ]);

        $response->assertOk()
            ->assertJson([
                'settings' => [
                    'default_zoom_duration_ms' => 2000,
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_update_settings(): void
    {
        $response = $this->putJson('/api/user/settings', [
            'auto_zoom_enabled' => false,
        ]);

        $response->assertStatus(401);
    }

    public function test_invalid_boolean_value_fails_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'auto_zoom_enabled' => 'not_a_boolean',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['auto_zoom_enabled']);
    }

    public function test_invalid_zoom_level_type_fails_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'default_zoom_level' => 'not_a_number',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['default_zoom_level']);
    }

    public function test_invalid_zoom_duration_type_fails_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'default_zoom_duration_ms' => 'not_an_integer',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['default_zoom_duration_ms']);
    }

    // ==========================================
    // RESET SETTINGS TESTS
    // ==========================================

    public function test_user_can_reset_settings_to_defaults(): void
    {
        // First set custom values
        UserSetting::create([
            'user_id' => $this->user->id,
            'key' => 'auto_zoom_enabled',
            'value' => 'false',
            'type' => 'boolean',
        ]);
        UserSetting::create([
            'user_id' => $this->user->id,
            'key' => 'default_zoom_level',
            'value' => '3.5',
            'type' => 'float',
        ]);
        UserSetting::create([
            'user_id' => $this->user->id,
            'key' => 'default_zoom_duration_ms',
            'value' => '1500',
            'type' => 'integer',
        ]);

        // Reset to defaults
        $response = $this->actingAs($this->user)
            ->postJson('/api/user/settings/reset');

        $response->assertOk()
            ->assertJson([
                'message' => 'Settings reset to defaults',
                'settings' => [
                    'auto_zoom_enabled' => true,
                    'default_zoom_level' => 2.0,
                    'default_zoom_duration_ms' => 500,
                ],
            ]);

        // Verify database has default values
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $this->user->id,
            'key' => 'auto_zoom_enabled',
            'value' => 'true',
        ]);
    }

    public function test_unauthenticated_user_cannot_reset_settings(): void
    {
        $response = $this->postJson('/api/user/settings/reset');

        $response->assertStatus(401);
    }

    // ==========================================
    // USER ISOLATION TESTS
    // ==========================================

    public function test_user_cannot_see_other_users_settings(): void
    {
        $otherUser = User::factory()->create();

        // Create settings for other user
        UserSetting::create([
            'user_id' => $otherUser->id,
            'key' => 'auto_zoom_enabled',
            'value' => 'false',
            'type' => 'boolean',
        ]);

        // Current user should see their own defaults, not other user's settings
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/settings');

        $response->assertOk()
            ->assertJson([
                'settings' => [
                    'auto_zoom_enabled' => true, // Default, not other user's false
                ],
            ]);
    }

    public function test_updating_settings_does_not_affect_other_users(): void
    {
        $otherUser = User::factory()->create();

        // Create settings for other user
        UserSetting::create([
            'user_id' => $otherUser->id,
            'key' => 'auto_zoom_enabled',
            'value' => 'true',
            'type' => 'boolean',
        ]);

        // Update current user's settings
        $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'auto_zoom_enabled' => false,
            ]);

        // Other user's settings should be unchanged
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $otherUser->id,
            'key' => 'auto_zoom_enabled',
            'value' => 'true',
        ]);
    }

    // ==========================================
    // PERSISTENCE TESTS
    // ==========================================

    public function test_settings_persist_across_requests(): void
    {
        // Update settings
        $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'auto_zoom_enabled' => false,
                'default_zoom_level' => 2.8,
                'default_zoom_duration_ms' => 750,
            ]);

        // Get settings in a new request
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/settings');

        $response->assertOk()
            ->assertJson([
                'settings' => [
                    'auto_zoom_enabled' => false,
                    'default_zoom_level' => 2.8,
                    'default_zoom_duration_ms' => 750,
                ],
            ]);
    }

    public function test_multiple_updates_preserve_unchanged_settings(): void
    {
        // First update
        $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'auto_zoom_enabled' => false,
                'default_zoom_level' => 3.0,
            ]);

        // Second update - only change duration
        $this->actingAs($this->user)
            ->putJson('/api/user/settings', [
                'default_zoom_duration_ms' => 1200,
            ]);

        // Check all settings
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/settings');

        $response->assertOk()
            ->assertJson([
                'settings' => [
                    'auto_zoom_enabled' => false, // From first update
                    'default_zoom_level' => 3.0, // From first update
                    'default_zoom_duration_ms' => 1200, // From second update
                ],
            ]);
    }
}
