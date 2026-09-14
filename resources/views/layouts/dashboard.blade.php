<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · EntryPoint.lk</title>
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#0f172a">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" referrerpolicy="no-referrer">
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.14.1/cdn.min.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .display { font-family: 'Bebas Neue', sans-serif; letter-spacing: 1px; }
        .sb-sidenav-dark { background: #0f172a; }
        .sb-topnav { background: #0f172a !important; }
        .stat-card .icon { width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center; font-size: 1.25rem; }
        .table td, .table th { vertical-align: middle; }
        .badge { font-weight: 600; }
    </style>
    @stack('head')
</head>
<body class="sb-nav-fixed">
@php($user = auth()->user())
<nav class="sb-topnav navbar navbar-expand navbar-dark">
    <a class="navbar-brand ps-3 d-flex align-items-center gap-2" href="{{ $user->dashboardUrl() }}">
        <img src="{{ asset('images/brand/wordmark-white.png') }}" alt="EntryPoint.lk" style="height:24px;width:auto;">
        <span class="fw-light small text-white-50">{{ $user->isVendor() ? 'VENDOR' : 'ADMIN' }}</span>
    </a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    <div class="ms-auto me-3 d-flex align-items-center gap-3">
        <a href="{{ route('home') }}" class="text-white-50 small text-decoration-none d-none d-md-inline"><i class="fa-solid fa-globe me-1"></i>View site</a>
        <a href="{{ route('notifications.index') }}" class="text-white position-relative">
            <i class="fa-regular fa-bell"></i>
            @php($unread = $user->unreadNotifications()->count())
            @if($unread)<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem">{{ $unread }}</span>@endif
        </a>
        <span class="text-white-50 small d-none d-md-inline">{{ $user->name }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-light">Log out</button></form>
    </div>
</nav>

<div id="layoutSidenav">
    <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
            <div class="sb-sidenav-menu">
                <div class="nav">
                    @if($user->isVendor())
                        <div class="sb-sidenav-menu-heading">Vendor <span class="badge {{ $user->vendorStatus()->bsBadge() }} ms-1">{{ $user->vendorStatus()->label() }}</span></div>
                        <a class="nav-link {{ request()->routeIs('vendor.dashboard') ? 'active' : '' }}" href="{{ route('vendor.dashboard') }}"><div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>Dashboard</a>
                        <a class="nav-link {{ request()->routeIs('vendor.venues.*') && ! request()->routeIs('vendor.venues.services.*') ? 'active' : '' }}" href="{{ route('vendor.venues.index') }}"><div class="sb-nav-link-icon"><i class="fas fa-building"></i></div>My venues</a>
                        <a class="nav-link {{ request()->routeIs('vendor.bookings.*') ? 'active' : '' }}" href="{{ route('vendor.bookings.index') }}"><div class="sb-nav-link-icon"><i class="fas fa-calendar-check"></i></div>Bookings</a>
                        <a class="nav-link" href="{{ route('vendor.bookings.index', ['pending_payment' => 1]) }}"><div class="sb-nav-link-icon"><i class="fas fa-money-check"></i></div>Verify transfers</a>
                        @php($venues = $user->venues()->with('services')->get())
                        @if($venues->isNotEmpty())
                            <div class="sb-sidenav-menu-heading">Services by venue</div>
                            @foreach($venues as $v)
                                <a class="nav-link small" href="{{ route('vendor.venues.services.index', $v) }}"><div class="sb-nav-link-icon"><i class="fas fa-layer-group"></i></div>{{ Str::limit($v->name, 22) }} <span class="ms-auto badge bg-secondary">{{ $v->services->count() }}</span></a>
                            @endforeach
                        @endif
                    @else
                        <div class="sb-sidenav-menu-heading">Admin</div>
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>Dashboard</a>
                        @php($pendingVendors = \App\Models\VendorProfile::where('status', 'pending')->count())
                        <a class="nav-link {{ request()->routeIs('admin.vendors.*') ? 'active' : '' }}" href="{{ route('admin.vendors.index') }}"><div class="sb-nav-link-icon"><i class="fas fa-store"></i></div>Vendors @if($pendingVendors)<span class="ms-auto badge bg-warning text-dark">{{ $pendingVendors }}</span>@endif</a>
                        <a class="nav-link {{ request()->routeIs('admin.venues.*') ? 'active' : '' }}" href="{{ route('admin.venues.index') }}"><div class="sb-nav-link-icon"><i class="fas fa-building"></i></div>Venues</a>
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>Users</a>
                        <div class="sb-sidenav-menu-heading">Catalogue</div>
                        <a class="nav-link {{ request()->routeIs('admin.activity-types.*') ? 'active' : '' }}" href="{{ route('admin.activity-types.index') }}"><div class="sb-nav-link-icon"><i class="fas fa-medal"></i></div>Activity types</a>
                        <a class="nav-link {{ request()->routeIs('admin.games.*') ? 'active' : '' }}" href="{{ route('admin.games.index') }}"><div class="sb-nav-link-icon"><i class="fas fa-gamepad"></i></div>Games</a>
                        @if($user->isAdmin())
                            <div class="sb-sidenav-menu-heading">Platform</div>
                            <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}"><div class="sb-nav-link-icon"><i class="fas fa-sliders"></i></div>Site settings</a>
                            <a class="nav-link" href="{{ route('vendor.dashboard') }}"><div class="sb-nav-link-icon"><i class="fas fa-store-alt"></i></div>Vendor panel (as admin)</a>
                        @endif
                    @endif
                </div>
            </div>
            <div class="sb-sidenav-footer">
                <div class="small">Logged in as</div>
                {{ $user->role()->label() }}
            </div>
        </nav>
    </div>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4 py-4">
                @if(session('message'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
        <footer class="py-4 bg-light mt-auto">
            <div class="container-fluid px-4"><div class="small text-muted">&copy; {{ date('Y') }} EntryPoint.lk</div></div>
        </footer>
    </div>
</div>

<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/scripts.js') }}"></script>
@stack('scripts')
</body>
</html>
