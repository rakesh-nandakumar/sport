<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_button_is_available_on_customer_auth_screens(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Continue with Google');
        $this->get(route('login'))->assertOk()->assertSee('Continue with Google');
    }

    public function test_google_redirect_requires_credentials(): void
    {
        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
    }

    public function test_google_redirect_starts_the_google_authorization_flow_when_configured(): void
    {
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        Socialite::fake('google');

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://socialite.fake/google/authorize');
    }

    public function test_google_callback_creates_and_authenticates_a_customer(): void
    {
        Socialite::fake('google', GoogleUser::fake([
            'id' => 'google-123',
            'name' => 'Google Customer',
            'email' => 'customer@gmail.com',
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))->assertRedirect('/');

        $user = User::where('email', 'customer@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame(Role::Customer, $user->role_id);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_google_callback_links_an_existing_account_with_the_same_email(): void
    {
        $existing = User::factory()->create([
            'email' => 'existing@gmail.com',
            'google_id' => null,
            'role_id' => Role::Customer,
        ]);

        Socialite::fake('google', GoogleUser::fake([
            'id' => 'google-456',
            'email' => 'existing@gmail.com',
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))->assertRedirect('/');

        $this->assertAuthenticatedAs($existing);
        $this->assertSame('google-456', $existing->fresh()->google_id);
        $this->assertSame(1, User::where('email', 'existing@gmail.com')->count());
    }

    public function test_google_callback_rejects_an_unverified_email(): void
    {
        Socialite::fake('google', GoogleUser::fake([
            'id' => 'google-unverified',
            'email' => 'unverified@gmail.com',
            'email_verified' => false,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'unverified@gmail.com']);
    }
}
