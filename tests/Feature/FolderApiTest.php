<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FolderApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withProSubscription()->create();
    }

    // ==================
    // Folder CRUD Tests
    // ==================

    #[Test]
    public function user_can_create_folder(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/folders', [
                'name' => 'Marketing Videos',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Marketing Videos',
            ]);

        $this->assertDatabaseHas('folders', [
            'name' => 'Marketing Videos',
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function folder_creation_requires_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/folders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function cannot_create_folder_with_duplicate_name(): void
    {
        // Create first folder
        Folder::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My Folder',
        ]);

        // Try to create another with same name
        $response = $this->actingAs($this->user)
            ->postJson('/api/folders', [
                'name' => 'My Folder',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function different_users_can_have_same_folder_name(): void
    {
        $otherUser = User::factory()->create();

        // Other user creates a folder
        Folder::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'My Folder',
        ]);

        // Current user should be able to create folder with same name
        $response = $this->actingAs($this->user)
            ->postJson('/api/folders', [
                'name' => 'My Folder',
            ]);

        $response->assertStatus(201);
    }

    #[Test]
    public function user_can_list_their_folders(): void
    {
        Folder::factory()->count(3)->create(['user_id' => $this->user->id]);
        Folder::factory()->count(2)->create(); // Other user's folders

        $response = $this->actingAs($this->user)
            ->getJson('/api/folders');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'folders');
    }

    #[Test]
    public function user_can_update_folder(): void
    {
        $folder = Folder::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/folders/{$folder->id}", [
                'name' => 'New Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'New Name']);

        $this->assertDatabaseHas('folders', [
            'id' => $folder->id,
            'name' => 'New Name',
        ]);
    }

    #[Test]
    public function user_cannot_update_others_folder(): void
    {
        $otherUser = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/folders/{$folder->id}", [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function user_can_delete_folder(): void
    {
        $folder = Folder::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/folders/{$folder->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('folders', ['id' => $folder->id]);
    }

    #[Test]
    public function deleting_folder_does_not_delete_videos(): void
    {
        $folder = Folder::factory()->create(['user_id' => $this->user->id]);
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'folder_id' => $folder->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/folders/{$folder->id}");

        $response->assertStatus(200);

        // Video should still exist but folder_id should be null
        $this->assertDatabaseHas('videos', ['id' => $video->id]);
        $this->assertNull($video->fresh()->folder_id);
    }

    // ==================
    // Video-Folder Association Tests
    // ==================

    #[Test]
    public function user_can_move_video_to_folder(): void
    {
        $folder = Folder::factory()->create(['user_id' => $this->user->id]);
        $video = Video::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/folders/{$folder->id}/videos", [
                'video_ids' => [$video->id],
            ]);

        $response->assertStatus(200);
        $this->assertEquals($folder->id, $video->fresh()->folder_id);
    }

    #[Test]
    public function user_can_remove_video_from_folder(): void
    {
        $folder = Folder::factory()->create(['user_id' => $this->user->id]);
        $video = Video::factory()->create([
            'user_id' => $this->user->id,
            'folder_id' => $folder->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/folders/{$folder->id}/videos/{$video->id}");

        $response->assertStatus(200);
        $this->assertNull($video->fresh()->folder_id);
    }

    #[Test]
    public function user_can_get_videos_in_folder(): void
    {
        $folder = Folder::factory()->create(['user_id' => $this->user->id]);
        Video::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'folder_id' => $folder->id,
        ]);
        Video::factory()->count(2)->create(['user_id' => $this->user->id]); // Not in folder

        $response = $this->actingAs($this->user)
            ->getJson("/api/folders/{$folder->id}/videos");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'videos');
    }
}
