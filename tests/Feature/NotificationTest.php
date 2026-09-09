<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_their_own_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Notification::create([
            'user_id' => $user->id,
            'type' => 'general',
            'title' => 'Visible',
            'message' => 'Owned notification',
        ]);
        Notification::create([
            'user_id' => $otherUser->id,
            'type' => 'general',
            'title' => 'Hidden',
            'message' => 'Other notification',
        ]);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertOk();
        $items = $response->viewData('page')['props']['notifications']['data'];

        $this->assertCount(1, $items);
        $this->assertSame('Visible', $items[0]['title']);
    }

    public function test_mark_all_as_read_only_updates_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $owned = Notification::create([
            'user_id' => $user->id,
            'type' => 'general',
            'title' => 'Owned',
            'message' => 'Unread',
            'is_read' => false,
        ]);
        $notOwned = Notification::create([
            'user_id' => $otherUser->id,
            'type' => 'general',
            'title' => 'Not owned',
            'message' => 'Unread',
            'is_read' => false,
        ]);

        $this->actingAs($user)
            ->post('/notifications/read-all')
            ->assertRedirect();

        $this->assertTrue($owned->fresh()->is_read);
        $this->assertFalse($notOwned->fresh()->is_read);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = Notification::create([
            'user_id' => $otherUser->id,
            'type' => 'general',
            'title' => 'Private',
            'message' => 'Unread',
            'is_read' => false,
        ]);

        $this->actingAs($user)
            ->post("/notifications/{$notification->id}/read")
            ->assertNotFound();

        $this->assertFalse($notification->fresh()->is_read);
    }
}
