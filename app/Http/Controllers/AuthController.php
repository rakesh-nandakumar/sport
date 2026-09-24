<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\VendorStatus;
use App\Filament\Resources\VendorProfiles\VendorProfileResource;
use App\Models\ActivityType;
use App\Models\User;
use App\Notifications\BookingNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

        return redirect()->intended($request->user()->isCustomer() ? '/' : $request->user()->dashboardUrl())
            ->with('message', 'Welcome back, '.$request->user()->name.'!');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    /** Customer sign-up: name, email, phone, password. */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'phone' => ['required', 'regex:/^0\d{9}$/'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'phone.regex' => 'Enter a valid 10-digit Sri Lankan number, e.g. 0771234567.',
        ]);

        $user = User::create($data + ['role_id' => Role::Customer]);
        auth()->login($user);

        return redirect('/')->with('message', 'Welcome to EntryPoint.lk, '.$user->name.'!');
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

        auth()->login($user);

        return redirect()->route('filament.vendor.pages.dashboard')->with('message',
            $user->vendorStatus() === VendorStatus::Active
                ? 'Your vendor account is ready. Add your first venue to start taking bookings.'
                : 'Thanks! Your application is under review. You can set up venues and services now — they go live once our team activates your account.');
    }

    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('message', 'You have been logged out.');
    }
}
