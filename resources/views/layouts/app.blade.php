<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/jpg" href="/images/sporteeFav.jpg">
    <title>@yield('title', 'Sportee · Book any sport, any time')</title>

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
    <link rel="stylesheet" href="/css/sportee.css">
    @livewireStyles
    @stack('head')
</head>
<body class="bg-white text-gray-800 antialiased">

<header class="head">
    <a href="{{ route('home') }}" class="indoorLogo">SPORTEE</a>

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
                Alerts
                @php($unread = auth()->user()->unreadNotifications()->count())
                @if($unread)
                    <span class="nav-badge">{{ $unread > 9 ? '9+' : $unread }}</span>
                @endif
            </a>
            @if(auth()->user()->isVendor())
                <a href="{{ route('vendor.dashboard') }}" style="--i:4;">Vendor panel</a>
            @elseif(auth()->user()->isAdmin() || auth()->user()->isStaff())
                <a href="{{ route('admin.dashboard') }}" style="--i:4;">Admin</a>
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

<footer class="site-footer">
    <div class="footer-content">
        <h3>Sportee</h3>
        <p>Book any sport, game or activity across Sri Lanka in seconds. Futsal courts, cricket nets, gaming lounges, paintball arenas, badminton halls and more — pick a venue, pick a time, and play.</p>
        <ul class="socials">
            <li><a href="#"><i class="fa-brands fa-facebook"></i></a></li>
            <li><a href="#"><i class="fa-brands fa-x-twitter"></i></a></li>
            <li><a href="#"><i class="fa-brands fa-youtube"></i></a></li>
            <li><a href="#"><i class="fa-brands fa-instagram"></i></a></li>
        </ul>
    </div>
    <div class="footer-bottom">
        <p>copyright &copy; {{ date('Y') }} Sportee. <span>Play anywhere, any time.</span></p>
    </div>
</footer>

<x-flash />

@livewireScripts
@stack('scripts')
</body>
</html>
