<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function user_can_get_their_notifications(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'notifications' => [
                    '*' => [
                        'id',
                        'type',
                        'message',
                        'read',
                        'created_at',
                        'read_at',
                        'actor',
                    ],
                ],
                'pagination',
            ]);
    }

    #[Test]
    public function notifications_are_returned_in_descending_order(): void
    {
        $olderNotification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay(),
        ]);

        $newerNotification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $notifications = $response->json('notifications');
        $this->assertEquals($newerNotification->id, $notifications[0]['id']);
        $this->assertEquals($olderNotification->id, $notifications[1]['id']);
    }

    #[Test]
    public function notification_message_contains_html_formatting(): void
    {
        $video = Video::factory()->create(['user_id' => $this->user->id]);
        $viewer = User::factory()->create(['name' => 'John Viewer']);

        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_VIEWER,
            'message' => "<span class=\"font-medium text-gray-900\">{$viewer->name}</span> viewed your video \"{$video->title}\"",
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $notificationData = $response->json('notifications.0');
        $this->assertStringContainsString('<span class="font-medium', $notificationData['message']);
        $this->assertStringContainsString($viewer->name, $notificationData['message']);
    }

    #[Test]
    public function notifications_include_correct_type(): void
    {
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_COMMENT,
        ]);

        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_VIEWER,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $types = collect($response->json('notifications'))->pluck('type')->toArray();
        $this->assertContains(Notification::TYPE_COMMENT, $types);
        $this->assertContains(Notification::TYPE_VIEWER, $types);
    }

    #[Test]
    public function notifications_include_actor_information(): void
    {
        $actor = User::factory()->create(['name' => 'Jane Commenter']);

        Notification::factory()->create([
            'user_id' => $this->user->id,
            'actor_id' => $actor->id,
            'type' => Notification::TYPE_COMMENT,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $notification = $response->json('notifications.0');
        $this->assertEquals($actor->id, $notification['actor']['id']);
        $this->assertEquals($actor->name, $notification['actor']['name']);
    }

    #[Test]
    public function unread_notifications_have_null_read_at(): void
    {
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'read' => false,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $notification = $response->json('notifications.0');
        $this->assertFalse($notification['read']);
        $this->assertNull($notification['read_at']);
    }

    #[Test]
    public function read_notifications_have_read_at_timestamp(): void
    {
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'read' => true,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $notification = $response->json('notifications.0');
        $this->assertTrue($notification['read']);
        $this->assertNotNull($notification['read_at']);
    }

    #[Test]
    public function user_cannot_access_other_users_notifications(): void
    {
        $otherUser = User::factory()->create();

        $otherNotification = Notification::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $notificationIds = collect($response->json('notifications'))->pluck('id')->toArray();
        $this->assertNotContains($otherNotification->id, $notificationIds);
    }

    #[Test]
    public function user_cannot_mark_other_users_notification_as_read(): void
    {
        $otherUser = User::factory()->create();

        $otherNotification = Notification::factory()->create([
            'user_id' => $otherUser->id,
            'read' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/notifications/{$otherNotification->id}/read");

        $response->assertStatus(403);
    }

    #[Test]
    public function notifications_are_paginated(): void
    {
        Notification::factory()->count(25)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('notifications'));
        $this->assertTrue($response->json('pagination.has_more'));
        $this->assertEquals(25, $response->json('pagination.total'));
    }

    #[Test]
    public function notification_types_are_valid(): void
    {
        foreach (Notification::TYPES as $type) {
            Notification::factory()->create([
                'user_id' => $this->user->id,
                'type' => $type,
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $types = collect($response->json('notifications'))->pluck('type')->unique()->toArray();

        foreach (Notification::TYPES as $expectedType) {
            $this->assertContains($expectedType, $types);
        }
    }
}
