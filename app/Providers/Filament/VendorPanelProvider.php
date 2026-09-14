<?php

namespace App\Providers\Filament;

use App\Filament\Vendor\Widgets\ExpiringHoldsWidget;
use App\Filament\Vendor\Widgets\ScheduleCalendarWidget;
use App\Filament\Vendor\Widgets\UpcomingBookingsWidget;
use App\Filament\Vendor\Widgets\VendorStatsOverview;
use App\Filament\Vendor\Widgets\VendorStatusBanner;
use App\Filament\Vendor\Widgets\VendorVenuesWidget;
use App\Support\Impersonation;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class VendorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('vendor')
            ->path('vendor')
            ->brandName('EntryPoint.lk · Vendor')
            ->favicon(asset('favicon-32x32.png'))
            ->colors([
                'primary' => Color::Red,
            ])
            ->discoverResources(in: app_path('Filament/Vendor/Resources'), for: 'App\Filament\Vendor\Resources')
            ->discoverPages(in: app_path('Filament/Vendor/Pages'), for: 'App\Filament\Vendor\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Vendor/Widgets'), for: 'App\Filament\Vendor\Widgets')
            ->widgets([
                VendorStatusBanner::class,
                VendorStatsOverview::class,
                ScheduleCalendarWidget::class,
                UpcomingBookingsWidget::class,
                ExpiringHoldsWidget::class,
                VendorVenuesWidget::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" referrerpolicy="no-referrer">'),
            )
            // A staff member "signed in as this vendor" (Vendors → Sign in as vendor) sees a banner
            // with a one-click way back, exactly like the old dashboard layout did.
            ->renderHook(
                PanelsRenderHook::BODY_START,
                function (): HtmlString {
                    if (! Impersonation::active()) {
                        return new HtmlString('');
                    }

                    $impersonator = Impersonation::impersonator();

                    return new HtmlString(
                        '<div style="background:#111827;color:#fff;text-align:center;padding:.5rem 1rem;font-size:.875rem;position:relative;z-index:40">'
                        .'You are signed in as this vendor'.($impersonator ? ' ('.e($impersonator->name).' is watching)' : '').'. '
                        .'<form method="POST" action="'.route('impersonate.stop').'" style="display:inline">'
                        .csrf_field()
                        .'<button type="submit" style="text-decoration:underline;color:#fca5a5;background:none;border:none;cursor:pointer;font-size:inherit">Back to admin</button>'
                        .'</form>'
                        .'</div>'
                    );
                },
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
