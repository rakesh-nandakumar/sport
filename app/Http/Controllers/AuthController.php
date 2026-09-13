<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        return view('auth.register', ['asVendor' => false]);
    }

    public function showVendorRegister(): View
    {
        return view('auth.register', ['asVendor' => true]);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'phone' => ['required', 'regex:/^0\d{9}$/'],
            'password' => ['required', 'confirmed', 'min:8'],
            'account_type' => ['required', Rule::in(['customer', 'vendor'])],
        ], [
            'phone.regex' => 'Enter a valid 10-digit Sri Lankan number, e.g. 0771234567.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'role_id' => $data['account_type'] === 'vendor' ? Role::Vendor : Role::Customer,
        ]);

        auth()->login($user);

        return redirect($user->isVendor() ? route('vendor.venues.create') : '/')
            ->with('message', $user->isVendor()
                ? 'Your vendor account is ready. Add your first venue to start taking bookings.'
                : 'Welcome to Sportee, '.$user->name.'!');
    }

    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('message', 'You have been logged out.');
    }
}
