<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="/images/sporteeFav.jpg">
    <title>Sportee</title>

    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Lexend:wght@400;500&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="/css/styles.css">

    <link rel="dns-prefetch" href="//unpkg.com" />
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net" />
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/core/main.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid/main.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid/main.css" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ECFDF5', 100: '#D1FAE5', 200: '#A7F3D0', 300: '#6EE7B7',
                            400: '#34D399', 500: '#10B981', 600: '#059669', 700: '#047857',
                            800: '#065F46', 900: '#064E3B',
                        },
                        accent: {
                            400: '#FBBF24', 500: '#F59E0B', 600: '#D97706',
                        },
                    },
                    fontFamily: {
                        display: ['"Bebas Neue"', 'sans-serif'],
                    },
                },
            },
        };
    </script>

    <livewire:styles />
    @livewireStyles
</head>

<body>

    <header class="site-header" x-data="{ userMenuOpen: false }">
        <div class="site-header__inner">
            <a href="/" class="site-logo">
                <span class="site-logo__mark"><i class='bx bxs-basketball'></i></span>
                SPORTEE
            </a>

            <input type="checkbox" id="ch">

            <label for="ch" class="nav-toggle">
                <i class='bx bx-menu' id="menu"></i>
                <i class='bx bx-x' id="cancel"></i>
            </label>

            <nav class="navbar">

                <a href="/" class="nav-link">Home</a>

                @auth
                    @if(auth()->user()->role_id->value == 2)
                        <a href="/home/manage" class="nav-link">Manage</a>
                    @endif

                    @if(auth()->user()->role_id->value == 5)
                        <a href="/customer/history" class="nav-link">History</a>
                    @endif

                    <a href="/notifications" class="nav-link">Notifications</a>

                    @if(auth()->user()->role_id->value == 1)
                        <a href="/home/dashboard" class="nav-link">Dashboard</a>
                    @endif

                    <div class="user-menu" @click.away="userMenuOpen = false">
                        <button type="button" class="user-menu__trigger" @click="userMenuOpen = !userMenuOpen">
                            <span class="user-menu__avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="user-menu__name">{{ auth()->user()->name }}</span>
                            <i class='bx bx-chevron-down'></i>
                        </button>

                        <div class="user-menu__panel" x-cloak x-show="userMenuOpen"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95">
                            <div class="user-menu__label">Signed in as {{ auth()->user()->name }}</div>
                            <form method="POST" action="/logout">
                                @csrf
                                <button type="submit" class="user-menu__logout">
                                    <i class="fa-solid fa-right-from-bracket"></i> Log out
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="/login" class="nav-link">Login</a>
                    <a href="/register" class="btn btn-primary btn-sm">Sign up</a>
                @endauth

            </nav>
        </div>
    </header>




   <main>
    @yield('content')

   </main>




    <footer class="site-footer">
        <div class="site-footer__grid">
            <div class="site-footer__brand">
                <a href="/" class="site-logo">
                    <span class="site-logo__mark"><i class='bx bxs-basketball'></i></span>
                    SPORTEE
                </a>
                <p>Discover the essence of sports excellence with Sportee – your ultimate destination for dynamic athletic experiences. From state-of-the-art facilities to community-driven events, Sportee is committed to fostering a passion for sports.</p>
                <ul class="socials">
                    <li><a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a></li>
                    <li><a href="#" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a></li>
                    <li><a href="#" aria-label="Google Plus"><i class="fa-brands fa-google-plus"></i></a></li>
                    <li><a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a></li>
                    <li><a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a></li>
                </ul>
            </div>

            <div class="site-footer__col">
                <h4>Explore</h4>
                <ul>
                    <li><a href="/">Find an Indoor</a></li>
                    <li><a href="/#activities">Activities</a></li>
                    <li><a href="/home/create">List your Indoor</a></li>
                </ul>
            </div>

            <div class="site-footer__col">
                <h4>Account</h4>
                <ul>
                    @auth
                        <li><a href="/notifications">Notifications</a></li>
                        @if(auth()->user()->role_id->value == 5)
                            <li><a href="/customer/history">My Bookings</a></li>
                        @endif
                        @if(auth()->user()->role_id->value == 2)
                            <li><a href="/home/manage">Manage Indoors</a></li>
                        @endif
                    @else
                        <li><a href="/login">Login</a></li>
                        <li><a href="/register">Sign up</a></li>
                    @endauth
                </ul>
            </div>

            <div class="site-footer__col">
                <h4>Get in touch</h4>
                <ul>
                    <li class="contact-line"><i class="fa-solid fa-location-dot"></i> Colombo, Sri Lanka</li>
                    <li class="contact-line"><i class="fa-solid fa-envelope"></i> hello@sportee.app</li>
                </ul>
            </div>
        </div>

        <div class="site-footer__bottom">
            <p>&copy; {{ date('Y') }} Sportee. All rights reserved.</p>
            <p>Built for players who don't like waiting</p>
        </div>
    </footer>

    <x-message />

    @stack('scripts')
    @livewireScripts
</body>

</html>
