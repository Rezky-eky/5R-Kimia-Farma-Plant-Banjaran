<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\GoBoost;
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

    public function test_go_boost_notifications_link_to_the_specific_detail(): void
    {
        $creator = User::factory()->create();
        $mentionedUser = User::factory()->create();
        $goBoost = GoBoost::create([
            'user_id' => $creator->id,
            'mentioned_user_id' => $mentionedUser->id,
            'area_temuan' => 'Area Produksi',
            'ruangan_temuan' => 'Ruang A',
            'penjelasan_temuan' => 'Temuan yang perlu diperbaiki',
            'pic_terkait' => 'PIC Produksi',
        ]);
        $notificationTypesByUser = [
            $creator->id => [
                'go_boost_perbaikan',
                'go_boost_approved_finder',
                'go_boost_rejected_finder',
            ],
            $mentionedUser->id => [
                'go_boost_mention',
                'go_boost_approved_solver',
                'go_boost_rejected_solver',
            ],
        ];

        foreach ($notificationTypesByUser as $userId => $types) {
            foreach ($types as $type) {
                Notification::create([
                    'user_id' => $userId,
                    'go_boost_id' => $goBoost->id,
                    'type' => $type,
                    'title' => 'GO BOOST notification',
                    'message' => 'Notification linked to a GO BOOST.',
                ]);
            }
        }

        foreach ([$creator, $mentionedUser] as $user) {
            $response = $this->actingAs($user)->get('/notifications');

            $response->assertOk();
            $notifications = $response->viewData('page')['props']['notifications']['data'];
            $this->assertCount(3, $notifications);

            foreach ($notifications as $notification) {
                $this->assertSame($goBoost->id, $notification['go_boost']['id']);
                $this->assertSame(
                    '/go-boost/' . $goBoost->id,
                    route('go_boost.show', $notification['go_boost']['id'], false)
                );
            }

            $this->get(route('go_boost.show', $goBoost->id))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('GoBoost/Show')
                    ->where('goBoost.id', $goBoost->id)
                    ->where('goBoost.penjelasan_temuan', 'Temuan yang perlu diperbaiki')
                );
        }
    }

    public function test_unrelated_user_cannot_view_go_boost_detail(): void
    {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $goBoost = GoBoost::create([
            'user_id' => $creator->id,
            'area_temuan' => 'Area Produksi',
            'ruangan_temuan' => 'Ruang A',
            'penjelasan_temuan' => 'Temuan yang perlu diperbaiki',
            'pic_terkait' => 'PIC Produksi',
        ]);

        $this->actingAs($otherUser)
            ->get(route('go_boost.show', $goBoost->id))
            ->assertForbidden();
    }
}
