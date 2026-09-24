<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\VendorStatus;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\ActivityTypes\Pages\CreateActivityType;
use App\Filament\Resources\VendorProfiles\Pages\ListVendorProfiles;
use App\Filament\Resources\Venues\Pages\ListVenues;
use App\Filament\Vendor\Resources\Venues\Pages\CreateVenue;
use App\Filament\Vendor\Resources\Venues\Schemas\VenueForm;
use App\Models\ActivityType;
use App\Models\Service;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Support\Settings;
use Carbon\Carbon;
use Database\Seeders\ActivityTypeSeeder;
use Database\Seeders\VenueSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Auth\Notifications\VerifyEmail;
use Livewire\Livewire;
use Tests\TestCase;

class VendorModerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-14 09:00:00');
        $this->admin = User::factory()->create(['role_id' => Role::SuperAdministrator]);
        $this->seed([ActivityTypeSeeder::class, VenueSeeder::class]);
    }

    public function test_vendor_application_collects_business_details_and_starts_pending(): void
    {
        Storage::fake('local');
        Notification::fake();

        $this->get('/register/vendor')->assertOk()->assertSee('Business registration certificate')->assertSee('Owner / director NIC')->assertSee('District');

        $response = $this->post('/register/vendor', $this->application());

        $response->assertRedirect(route('verification.notice'));
        $user = User::where('email', 'owner@newarena.lk')->firstOrFail();
        $this->assertSame(Role::Vendor, $user->role_id);
        $this->assertSame(VendorStatus::Pending, $user->vendorStatus());
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);

        $profile = $user->vendorProfile;
        $this->assertSame('New Arena (Pvt) Ltd', $profile->business_name);
        $this->assertSame('PV123456', $profile->registration_number);
        $this->assertSame('Kandy', $profile->district);
        $this->assertEqualsWithDelta(7.2906, $profile->latitude, 0.0001);
        $this->assertNotNull($profile->terms_accepted_at);
        Storage::disk('local')->assertExists($profile->br_document_path);
        Storage::disk('local')->assertExists($profile->nic_document_path);

        // Admin is told about it.
        $this->assertTrue($this->admin->fresh()->notifications->pluck('data.message')->contains(fn ($m) => str_contains($m, 'New vendor application')));

        // The vendor must verify their email before accessing the workspace.
        $this->actingAs($user)->get(route('filament.vendor.pages.dashboard'))->assertRedirect(route('verification.notice'));
        $user->markEmailAsVerified();

        // Once verified, the vendor sees their status and can prepare a venue.
        $this->actingAs($user)->get(route('filament.vendor.pages.dashboard'))->assertOk()->assertSee('Pending review')->assertSee('reviewing your application');
        $this->actingAs($user)->get(route('filament.vendor.resources.venues.create'))->assertOk()->assertSee('New Arena (Pvt) Ltd');
    }

    public function test_companies_must_supply_registration_number_and_certificate(): void
    {
        $this->post('/register/vendor', array_merge($this->application(), ['registration_number' => '', 'br_document' => null]))
            ->assertSessionHasErrors(['registration_number', 'br_document']);

        $this->post('/register/vendor', array_merge($this->application(), ['owner_nic' => '12345', 'terms' => null, 'district' => 'Atlantis']))
            ->assertSessionHasErrors(['owner_nic', 'terms', 'district']);

        $this->assertNull(User::where('email', 'owner@newarena.lk')->first());
    }

    public function test_pending_vendors_venues_stay_hidden_until_an_admin_activates_them(): void
    {
        Storage::fake('local');
        $this->post('/register/vendor', $this->application());
        $vendor = User::where('email', 'owner@newarena.lk')->firstOrFail();

        $this->createVenueViaFilament($vendor);
        $venue = Venue::where('name', 'New Arena Courts')->firstOrFail();
        $this->assertTrue($venue->is_approved, 'venue approval itself is not required by default');
        $this->assertFalse($venue->isLive(), 'but the vendor is still pending');

        $this->get('/venues')->assertOk()->assertDontSee('New Arena Courts');
        $this->actingAs(User::factory()->create(['role_id' => Role::Customer]))->get(route('venues.show', $venue))->assertNotFound();
        $this->actingAs($vendor)->get(route('venues.show', $venue))->assertOk(); // owner can preview

        // Activate from the admin panel.
        Livewire::actingAs($this->admin)->test(ListVendorProfiles::class)
            ->filterTable('status', 'pending')
            ->assertCanSeeTableRecords([$vendor->vendorProfile]);

        $this->actingAs($this->admin)->get(route('filament.admin.resources.vendors.view', $vendor->vendorProfile))->assertOk()->assertSee('PV123456')->assertSee('Owner NIC');
        $this->actingAs($this->admin)->get(route('admin.vendors.document', [$vendor->vendorProfile, 'nic']))->assertOk();

        Livewire::actingAs($this->admin)->test(ListVendorProfiles::class)
            ->callTableAction('activate', $vendor->vendorProfile)
            ->assertHasNoTableActionErrors();

        $this->assertSame(VendorStatus::Active, $vendor->fresh()->vendorStatus());
        $this->assertTrue($venue->fresh()->isLive());
        $this->get('/venues?q=New+Arena')->assertOk()->assertSee('New Arena Courts');
        $this->assertTrue($vendor->fresh()->notifications->pluck('data.message')->contains(fn ($m) => str_contains($m, 'activated')));
    }

    public function test_suspending_a_vendor_hides_their_venues_and_needs_a_reason(): void
    {
        $vendor = User::where('email', 'vendor@entrypoint.lk')->firstOrFail();
        $profile = $vendor->vendorProfile;
        $court = Service::where('name', 'Court A (Main)')->firstOrFail();

        $this->get('/venues')->assertSee('CR7 Futsal Arena');

        Livewire::actingAs($this->admin)->test(ListVendorProfiles::class)
            ->callTableAction('changeStatus', $profile, data: ['status' => 'suspended'])
            ->assertHasTableActionErrors(['review_notes']);

        Livewire::actingAs($this->admin)->test(ListVendorProfiles::class)
            ->callTableAction('changeStatus', $profile, data: ['status' => 'suspended', 'review_notes' => 'Repeated no-shows on confirmed bookings.'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(VendorStatus::Suspended, $vendor->fresh()->vendorStatus());
        $this->get('/venues')->assertDontSee('venues/cr7-futsal-arena');
        $this->get('/')->assertDontSee('venues/cr7-futsal-arena');
        $this->get(route('booking.build', $court))->assertNotFound();
        $this->actingAs($vendor->fresh())->get(route('filament.vendor.pages.dashboard'))->assertOk()->assertSee('Suspended')->assertSee('Repeated no-shows');
    }

    public function test_only_super_admins_can_change_vendor_status_or_settings(): void
    {
        $moderator = User::factory()->create(['role_id' => Role::Moderator]);
        $profile = VendorProfile::firstOrFail();

        $this->actingAs($moderator)->get(route('filament.admin.resources.vendors.index'))->assertOk();
        $this->actingAs($moderator)->get(route('filament.admin.resources.vendors.view', $profile))->assertOk();

        Livewire::actingAs($moderator)->test(ListVendorProfiles::class)
            ->assertTableActionHidden('changeStatus', $profile)
            ->assertTableActionHidden('activate', $profile);

        $this->actingAs($moderator)->get(route('filament.admin.pages.settings'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role_id' => Role::Vendor]))->get(route('filament.admin.resources.vendors.index'))->assertForbidden();
    }

    public function test_super_admin_can_change_site_settings(): void
    {
        $this->actingAs($this->admin)->get(route('filament.admin.pages.settings'))->assertOk()->assertSee('Bank-transfer verification window')->assertSee('Coming soon');

        Livewire::actingAs($this->admin)->test(ManageSettings::class)
            ->fillForm([
                'payments_enabled' => ['pay_at_venue', 'card'], // card is not integrated -> ignored
                'bank_transfer_hold_minutes' => 30,
                'vendors_require_activation' => true,
                'venues_require_approval' => true,
                'max_days_ahead' => 21,
                'nearby_km' => 40,
                'amenities' => "Parking\nSauna\n\nParking",
                'support_email' => 'help@entrypoint.lk',
                'support_phone' => '0771234567',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        Settings::flush();
        $this->assertSame(['pay_at_venue'], setting('payments.enabled'));
        $this->assertSame(30, setting('payments.bank_transfer_hold_minutes'));
        $this->assertTrue(setting('venues.require_approval'));
        $this->assertSame(21, setting('bookings.max_days_ahead'));
        $this->assertSame(['Parking', 'Sauna'], setting('venues.amenities'));

        // With venue approval on, a new venue from an active vendor is not live until approved.
        $vendor = User::where('email', 'vendor@entrypoint.lk')->firstOrFail();
        $this->createVenueViaFilament($vendor);
        $venue = Venue::where('name', 'New Arena Courts')->firstOrFail();
        $this->assertFalse($venue->is_approved);

        Livewire::actingAs($this->admin)->test(ListVenues::class)
            ->callTableAction('toggleApproval', $venue)
            ->assertHasNoTableActionErrors();

        $this->assertTrue($venue->fresh()->isLive());

        // Nobody can switch every method off.
        Livewire::actingAs($this->admin)->test(ManageSettings::class)
            ->fillForm([
                'payments_enabled' => [],
                'bank_transfer_hold_minutes' => 30, 'max_days_ahead' => 21, 'nearby_km' => 40, 'amenities' => 'Parking',
                'support_email' => 'help@entrypoint.lk', 'support_phone' => '0771234567',
            ])
            ->call('save')
            ->assertHasFormErrors(['payments_enabled']);
    }

    public function test_activity_type_photo_can_be_uploaded_by_admin(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin)->test(CreateActivityType::class)
            ->fillForm([
                'name' => 'Go-karting',
                'icon' => 'fa-solid fa-flag-checkered',
                'color' => '#111111',
                'unit_label' => 'Kart',
                'default_slot_minutes' => 15,
                'sort_order' => 20,
                'image_upload' => UploadedFile::fake()->image('karts.jpg', 1200, 800),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $type = ActivityType::where('slug', 'go-karting')->firstOrFail();
        Storage::disk('public')->assertExists($type->image);
        $this->assertStringContainsString('storage/activity-types', $type->imageUrl());
    }

    public function test_staff_can_sign_in_as_a_vendor_and_switch_back(): void
    {
        $vendor = User::where('email', 'vendor@entrypoint.lk')->firstOrFail();

        Livewire::actingAs($this->admin)->test(ListVendorProfiles::class)
            ->callTableAction('impersonate', $vendor->vendorProfile)
            ->assertHasNoTableActionErrors()
            ->assertRedirect(route('filament.vendor.pages.dashboard'));

        $this->assertSame($vendor->id, auth()->id());
        $this->get(route('filament.vendor.pages.dashboard'))->assertOk()->assertSee('You are signed in as')->assertSee('Back to admin');

        $this->post(route('impersonate.stop'))->assertRedirect(route('filament.admin.resources.vendors.index'));
        $this->assertSame($this->admin->id, auth()->id());
        $this->get(route('filament.vendor.pages.dashboard'))->assertDontSee('Back to admin');
    }

    public function test_only_staff_can_start_impersonation_and_customers_cannot_stop_it(): void
    {
        $vendor = User::where('email', 'vendor@entrypoint.lk')->firstOrFail();
        $marketing = User::factory()->create(['role_id' => Role::MarketingManager]);

        Livewire::actingAs($marketing)->test(ListVendorProfiles::class)
            ->assertTableActionHidden('impersonate', $vendor->vendorProfile);

        $customer = User::factory()->create(['role_id' => Role::Customer]);
        $this->actingAs($customer)->post(route('impersonate.stop'))->assertForbidden();
    }

    public function test_moderator_can_sign_in_as_a_vendor_and_switch_back(): void
    {
        $vendor = User::where('email', 'vendor@entrypoint.lk')->firstOrFail();
        $moderator = User::factory()->create(['role_id' => Role::Moderator]);

        Livewire::actingAs($moderator)->test(ListVendorProfiles::class)
            ->callTableAction('impersonate', $vendor->vendorProfile)
            ->assertHasNoTableActionErrors()
            ->assertRedirect(route('filament.vendor.pages.dashboard'));

        $this->assertSame($vendor->id, auth()->id());
        $this->get(route('filament.vendor.pages.dashboard'))->assertOk()->assertSee('Back to admin');

        $this->post(route('impersonate.stop'))->assertRedirect(route('filament.admin.resources.vendors.index'));
        $this->assertSame($moderator->id, auth()->id());
    }

    protected function application(): array
    {
        return [
            'name' => 'Nuwan Owner', 'email' => 'owner@newarena.lk', 'phone' => '0771112222', 'password' => 'password123', 'password_confirmation' => 'password123',
            'business_name' => 'New Arena (Pvt) Ltd', 'business_type' => 'private_limited', 'registration_number' => 'PV123456', 'owner_nic' => '199012345678',
            'contact_person' => 'Nuwan Owner', 'contact_phone' => '0812223344', 'business_email' => 'info@newarena.lk', 'website' => 'https://newarena.lk',
            'description' => 'Two indoor futsal courts and a badminton hall in the heart of Kandy, running since 2019.', 'years_operating' => 6, 'venue_count_estimate' => 1,
            'activity_type_ids' => [1, 4],
            'address_line1' => '10 Peradeniya Road', 'city' => 'Kandy', 'district' => 'Kandy', 'postal_code' => '20000', 'latitude' => 7.2906, 'longitude' => 80.6337,
            'br_document' => UploadedFile::fake()->create('br.pdf', 200, 'application/pdf'),
            'nic_document' => UploadedFile::fake()->image('nic.jpg'),
            'terms' => 1,
        ];
    }

    /** Creates "New Arena Courts" for $vendor through the Filament vendor panel, like the old form POST did. */
    protected function createVenueViaFilament(User $vendor): void
    {
        Filament::setCurrentPanel('vendor');

        Livewire::actingAs($vendor)->test(CreateVenue::class)
            ->fillForm([
                'name' => 'New Arena Courts', 'address' => '10 Peradeniya Road', 'city' => 'Kandy', 'district' => 'Kandy', 'phone' => '0812223344',
                'latitude' => 7.2906, 'longitude' => 80.6337,
                'hours' => VenueForm::defaultHours(null),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // The rest of the test acts in the admin panel; Filament's "current panel" otherwise stays
        // stuck on 'vendor' for any Livewire component resolved for the remainder of this test.
        Filament::setCurrentPanel('admin');
    }
}
