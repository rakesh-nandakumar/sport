<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\BookingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_no_notification_bell(): void
    {
        $this->get('/')->assertOk()->assertDontSee('id="notifBell"', false);
    }

    public function test_user_with_no_notifications_sees_bell_with_empty_state_and_view_all(): void
    {
        $user = User::factory()->create(['role_id' => Role::Customer]);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('id="notifBell"', false)
            ->assertSee('id="notifDrop"', false)
            ->assertSee("You're all caught up.", false)
            ->assertSee('View all', false)
            ->assertSee(route('notifications.index'), false);
    }

    public function test_bell_shows_unread_badge_and_only_five_latest_notifications(): void
    {
        $user = User::factory()->create(['role_id' => Role::Customer]);

        for ($i = 1; $i <= 7; $i++) {
            $user->notify(new BookingNotification(message: "Bell preview message {$i}"));
            // Stagger timestamps so "latest" ordering is deterministic (same-second ties
            // otherwise come back in insertion order).
            $user->notifications()->where('data->message', "Bell preview message {$i}")
                ->update(['created_at' => now()->subMinutes(7 - $i), 'updated_at' => now()->subMinutes(7 - $i)]);
        }

        $response = $this->actingAs($user)->get('/')->assertOk();

        $response->assertSee('<span class="nav-badge">7</span>', false);

        foreach ([7, 6, 5, 4, 3] as $i) {
            $response->assertSee("Bell preview message {$i}");
        }

        foreach ([1, 2] as $i) {
            $response->assertDontSee("Bell preview message {$i}");
        }

        $response->assertSee('View all', false)
            ->assertSee(route('notifications.index'), false);
    }
}
