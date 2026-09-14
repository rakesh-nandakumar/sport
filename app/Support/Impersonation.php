<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;

class Impersonation
{
    private const SESSION_KEY = 'impersonator_id';

    public static function active(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public static function impersonator(): ?User
    {
        return self::active() ? User::find(session(self::SESSION_KEY)) : null;
    }

    /** Super admins and moderators may sign in as a vendor; use this for route/action visibility too. */
    public static function allowed(?User $user): bool
    {
        return (bool) $user?->hasRole(Role::SuperAdministrator, Role::Moderator);
    }

    public static function start(User $admin, User $target): void
    {
        abort_unless(self::allowed($admin) && ! self::active(), 403);
        abort_unless($target->isVendor(), 404);

        session()->put(self::SESSION_KEY, $admin->id);
        auth()->login($target);
        session()->regenerate();
    }

    public static function stop(): User
    {
        $admin = self::impersonator();
        abort_unless(self::allowed($admin), 403);

        session()->forget(self::SESSION_KEY);
        auth()->login($admin);
        session()->regenerate();

        return $admin;
    }
}
