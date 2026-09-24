<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\VendorStatus;
use App\Filament\Resources\VendorProfiles\VendorProfileResource;
use App\Models\ActivityType;
use App\Models\User;
use App\Notifications\BookingNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! auth()->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Those credentials do not match our records.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if (! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended($request->user()->isCustomer() ? '/' : $request->user()->dashboardUrl())
            ->with('message', 'Welcome back, '.$request->user()->name.'!');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    /** Customer sign-up: name, email, phone, password, and optional Pay at Venue identity images. */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'phone' => ['required', 'regex:/^0\d{9}$/'],
            'password' => ['required', 'confirmed', 'min:8'],
            'nic_front' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120', 'required_with:nic_back'],
            'nic_back' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120', 'required_with:nic_front'],
        ], [
            'phone.regex' => 'Enter a valid 10-digit Sri Lankan number, e.g. 0771234567.',
            'nic_front.required_with' => 'Upload both the front and back of your NIC, or skip both for now.',
            'nic_back.required_with' => 'Upload both the front and back of your NIC, or skip both for now.',
        ]);

        $user = DB::transaction(function () use ($request, $data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role_id' => Role::Customer,
            ]);

            if ($request->hasFile('nic_front')) {
                $user->update([
                    'nic_front_path' => $request->file('nic_front')->store('identity-documents/'.$user->id, 'local'),
                    'nic_back_path' => $request->file('nic_back')->store('identity-documents/'.$user->id, 'local'),
                ]);
            }

            return $user;
        });

        event(new Registered($user));
        auth()->login($user);

        return redirect()->route('verification.notice')->with('message', 'We sent a verification link to '.$user->email.'. Please verify your email to start booking.');
    }

    /** Send a customer to Google to create an account or sign in. */
    public function redirectToGoogle(): RedirectResponse
    {
        if (! $this->googleIsConfigured()) {
            return redirect()->route('login')->withErrors([
                'google' => 'Google sign-in is not available right now. Please try again later.',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    /** Create or link a customer account after Google has authenticated them. */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors([
                'google' => 'We could not complete Google sign-in. Please try again.',
            ]);
        }

        if (! $this->hasVerifiedGoogleEmail($googleUser)) {
            return redirect()->route('login')->withErrors([
                'google' => 'Google did not provide a verified email address for this account.',
            ]);
        }

        $googleId = (string) $googleUser->getId();
        $email = Str::lower((string) $googleUser->getEmail());

        [$user, $created] = DB::transaction(function () use ($googleUser, $googleId, $email): array {
            // Google subject IDs are stable even when a person changes their email address.
            $user = User::where('google_id', $googleId)->lockForUpdate()->first();

            if ($user) {
                return [$user, false];
            }

            // A verified Google email may link an existing password account. This avoids duplicate
            // customer accounts while never changing a user's role or profile details.
            $user = User::where('email', $email)->lockForUpdate()->first();

            if ($user) {
                $user->forceFill([
                    'google_id' => $googleId,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();

                return [$user, false];
            }

            return [User::create([
                'name' => Str::of($googleUser->getName() ?: $email)->squish()->limit(100, '')->value(),
                'email' => $email,
                'google_id' => $googleId,
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(64)),
                'role_id' => Role::Customer,
            ]), true];
        });

        auth()->login($user, true);
        $request->session()->regenerate();

        return redirect()->intended($user->isCustomer() ? '/' : $user->dashboardUrl())->with(
            'message',
            $created ? 'Welcome to EntryPoint.lk, '.$user->name.'!' : 'Welcome back, '.$user->name.'!'
        );
    }

    public function showVendorRegister(): View
    {
        return view('auth.register-vendor', [
            'districts' => array_keys(config('entrypoint.districts')),
            'businessTypes' => config('entrypoint.business_types'),
            'activityTypes' => ActivityType::orderBy('sort_order')->get(),
        ]);
    }

    /**
     * Vendor application: the account plus everything an admin needs to verify the business
     * (registration number, NIC, address, documents). The account starts as "pending review" —
     * venues stay hidden until an admin activates it.
     */
    public function registerVendor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // Account
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'phone' => ['required', 'regex:/^0\d{9}$/'],
            'password' => ['required', 'confirmed', 'min:8'],
            // Business
            'business_name' => ['required', 'string', 'min:2', 'max:120'],
            'business_type' => ['required', Rule::in(array_keys(config('entrypoint.business_types')))],
            'registration_number' => ['nullable', 'string', 'max:60', 'required_if:business_type,private_limited,partnership'],
            'owner_nic' => ['required', 'regex:/^(\d{9}[VvXx]|\d{12})$/'],
            'contact_person' => ['required', 'string', 'max:100'],
            'contact_phone' => ['required', 'regex:/^0\d{9}$/'],
            'alt_phone' => ['nullable', 'regex:/^0\d{9}$/'],
            'business_email' => ['required', 'email'],
            'website' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:30', 'max:2000'],
            'years_operating' => ['nullable', 'integer', 'min:0', 'max:100'],
            'venue_count_estimate' => ['nullable', 'integer', 'min:1', 'max:100'],
            'activity_type_ids' => ['nullable', 'array'],
            'activity_type_ids.*' => ['integer', 'exists:activity_types,id'],
            // Location
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:80'],
            'district' => ['required', Rule::in(array_keys(config('entrypoint.districts')))],
            'postal_code' => ['nullable', 'digits:5'],
            'latitude' => ['nullable', 'numeric', 'between:5.5,10.5'],
            'longitude' => ['nullable', 'numeric', 'between:79,82.5'],
            // Documents
            'br_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192', 'required_if:business_type,private_limited,partnership'],
            'nic_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
            'terms' => ['accepted'],
        ], [
            'phone.regex' => 'Enter a valid 10-digit Sri Lankan number, e.g. 0771234567.',
            'contact_phone.regex' => 'Enter a valid 10-digit number.',
            'alt_phone.regex' => 'Enter a valid 10-digit number.',
            'owner_nic.regex' => 'Enter a valid NIC (old format 123456789V or new 12-digit format).',
            'registration_number.required_if' => 'A business registration number is required for companies and partnerships.',
            'br_document.required_if' => 'Please upload the business registration certificate.',
            'nic_document.required' => 'Please upload a copy of the owner\'s NIC.',
            'terms.accepted' => 'You need to accept the vendor terms to continue.',
        ]);

        $user = DB::transaction(function () use ($request, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role_id' => Role::Vendor,
            ]);

            // Documents go to the private disk; only admins can open them (Admin\VendorController@document).
            $profile = collect($data)->except(['name', 'email', 'phone', 'password', 'br_document', 'nic_document', 'terms'])->all();
            $profile['br_document_path'] = $request->hasFile('br_document') ? $request->file('br_document')->store('vendor-documents/'.$user->id, 'local') : null;
            $profile['nic_document_path'] = $request->file('nic_document')->store('vendor-documents/'.$user->id, 'local');
            $profile['status'] = setting('vendors.require_activation') ? VendorStatus::Pending : VendorStatus::Active;
            $profile['terms_accepted_at'] = now();
            $profile['activity_type_ids'] = array_map('intval', $data['activity_type_ids'] ?? []);

            $user->vendorProfile()->create($profile);

            return $user;
        });

        foreach (User::where('role_id', Role::SuperAdministrator)->get() as $admin) {
            $admin->notify(new BookingNotification(
                "New vendor application: {$data['business_name']} ({$data['city']}, {$data['district']}) by {$user->name}. Review and activate it from the admin panel.",
                'system',
                null,
                VendorProfileResource::getUrl('view', ['record' => $user->vendorProfile]),
            ));
        }

        event(new Registered($user));
        auth()->login($user);

        return redirect()->route('verification.notice')->with('message', 'We sent a verification link to '.$user->email.'. Verify your email before accessing the vendor workspace.');
    }

    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('message', 'You have been logged out.');
    }

    private function googleIsConfigured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    private function hasVerifiedGoogleEmail(SocialiteUser $googleUser): bool
    {
        $raw = method_exists($googleUser, 'getRaw') ? $googleUser->getRaw() : [];
        $emailVerified = $raw['email_verified'] ?? $raw['verified_email'] ?? false;

        return filled($googleUser->getId())
            && filled($googleUser->getEmail())
            && filter_var($emailVerified, FILTER_VALIDATE_BOOLEAN);
    }
}
