<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationAndIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_registration_sends_a_verification_email_and_can_save_optional_nic_images(): void
    {
        Notification::fake();
        Storage::fake('local');

        $this->post(route('register'), [
            'name' => 'Verified Later',
            'email' => 'verified-later@example.com',
            'phone' => '0771234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'nic_front' => UploadedFile::fake()->image('nic-front.jpg'),
            'nic_back' => UploadedFile::fake()->image('nic-back.jpg'),
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'verified-later@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(Role::Customer, $user->role_id);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->hasNicOnFile());
        Storage::disk('local')->assertExists($user->nic_front_path);
        Storage::disk('local')->assertExists($user->nic_back_path);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_customer_must_supply_both_nic_images_or_skip_both(): void
    {
        $this->post(route('register'), [
            'name' => 'One Side Only',
            'email' => 'one-side@example.com',
            'phone' => '0771234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'nic_front' => UploadedFile::fake()->image('nic-front.jpg'),
        ])->assertSessionHasErrors('nic_back');

        $this->assertDatabaseMissing('users', ['email' => 'one-side@example.com']);
    }

    public function test_unverified_accounts_are_redirected_to_the_verification_notice(): void
    {
        $user = User::factory()->unverified()->create(['role_id' => Role::Customer]);

        $this->actingAs($user)
            ->get(route('bookings.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_signed_verification_link_marks_the_account_as_verified(): void
    {
        $user = User::factory()->unverified()->create(['role_id' => Role::Customer]);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect('/');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
