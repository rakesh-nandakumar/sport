<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#ef4444">
    <title>@yield('title', 'EntryPoint.lk · Book any sport, any time')</title>

    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" referrerpolicy="no-referrer">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['"Bebas Neue"', 'sans-serif'],
                        sans: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        brand: { DEFAULT: '#ef4444', dark: '#b91c1c', soft: '#fee2e2' },
                        ink: '#0f172a',
                    },
                },
            },
        }
    </script>

    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/theme.css">
    <link rel="stylesheet" href="/css/file-drop.css">
    @livewireStyles
    @stack('head')
</head>
@php($hasLocation = (bool) session(\App\Services\PersonalizationService::SESSION_KEY) || auth()->user()?->hasLocation() || request()->cookie(\App\Services\PersonalizationService::COOKIE))
<body class="bg-white text-gray-800 antialiased" data-has-location="{{ $hasLocation ? 1 : 0 }}">

<header class="head no-print">
    <a href="{{ route('home') }}" class="brand">
        <img src="{{ asset('images/brand/wordmark-white.png') }}" alt="EntryPoint.lk" class="brand-logo">
    </a>

    <input type="checkbox" id="ch">
    <label for="ch" class="icons">
        <i class="bx bx-menu" id="menu"></i>
        <i class="bx bx-x" id="cancel"></i>
    </label>

    <nav class="navbar">
        <a href="{{ route('venues.index') }}" style="--i:1;">Venues</a>
        @auth
            <a href="{{ route('bookings.index') }}" style="--i:2;">My bookings</a>
            <a href="{{ route('notifications.index') }}" style="--i:3;" class="relative">
                Notifications
                @php($unread = auth()->user()->unreadNotifications()->count())
                @if($unread)
                    <span class="nav-badge">{{ $unread > 9 ? '9+' : $unread }}</span>
                @endif
            </a>
            @if(auth()->user()->isVendor())
                <a href="{{ route('filament.vendor.pages.dashboard') }}" style="--i:4;">Vendor panel</a>
            @elseif(auth()->user()->isAdmin() || auth()->user()->isStaff())
                <a href="{{ route('filament.admin.pages.dashboard') }}" style="--i:4;">Admin</a>
            @endif
            <form class="inline" method="POST" action="{{ route('logout') }}" style="--i:5;">
                @csrf
                <button type="submit" class="nav-logout"><i class="fa-solid fa-right-from-bracket mr-1"></i>Log out</button>
            </form>
        @else
            <a href="{{ route('login') }}" style="--i:2;">Login</a>
            <a href="{{ route('register') }}" style="--i:3;">Sign up</a>
            <a href="{{ route('register.vendor') }}" style="--i:4;" class="nav-cta">List your venue</a>
        @endauth
    </nav>
</header>

<main class="@yield('main-class', 'pt-24 md:pt-28 min-h-screen')">
    @yield('content')
</main>

<footer class="site-footer no-print">
    <div class="footer-content">
        <img src="{{ asset('images/brand/wordmark-white.png') }}" alt="EntryPoint.lk" class="footer-logo">
        <p>Book any sport, game or activity across Sri Lanka in seconds. Futsal courts, cricket nets, gaming lounges, paintball arenas, badminton halls and more — pick a venue, pick a time, and play.</p>
        <ul class="socials">
            <li><a href="#"><i class="fa-brands fa-facebook"></i></a></li>
            <li><a href="#"><i class="fa-brands fa-x-twitter"></i></a></li>
            <li><a href="#"><i class="fa-brands fa-youtube"></i></a></li>
            <li><a href="#"><i class="fa-brands fa-instagram"></i></a></li>
        </ul>
    </div>
    <div class="footer-bottom">
        <p>copyright &copy; {{ date('Y') }} EntryPoint.lk. <span>Play anywhere, any time.</span></p>
    </div>
</footer>

<x-flash />

@livewireScripts
<script>
    // Location for "near you" sorting. Asks the browser once per session on first visit (guests and
    // members alike); a district picker in the location bar is the fallback when permission is denied.
    window.epRequestLocation = function () {
        return new Promise(function (resolve) {
            if (! navigator.geolocation) { resolve(false); return; }
            navigator.geolocation.getCurrentPosition(function (pos) {
                fetch(@json(route('location.store')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
                }).then(function () { window.location.reload(); }).catch(function () { resolve(false); });
            }, function () { resolve(false); }, { enableHighAccuracy: false, timeout: 8000, maximumAge: 600000 });
        });
    };
    (function () {
        try {
            if (document.body.dataset.hasLocation === '1' || sessionStorage.getItem('ep_loc_asked')) return;
            sessionStorage.setItem('ep_loc_asked', '1');
            window.epRequestLocation();
        } catch (e) {}
    })();
</script>
@stack('scripts')
</body>
</html>
