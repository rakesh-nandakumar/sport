<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Dashboard - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="{{asset('assets/css/styles.css')}}" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js" integrity="sha512-wqY8fwjyJLzXKsdwkLNJoKjMqxJyqygRXfnOjFyvWCMYPWzYrEMCW2uxxpONvwBGywzvQCZKsWdQx0xnrlzqsZA=" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <style>
        :root{ --bs-primary: #059669; --bs-primary-rgb: 5, 150, 105; }
        .navbar-dark.bg-dark{ background-color: #111827 !important; }
        .sb-sidenav-dark{ background-color: #111827; }
        .sb-sidenav-dark .sb-sidenav-menu .nav-link.active{ background-color: rgba(5, 150, 105, .35); color: #fff; }
        .sb-sidenav-dark .sb-sidenav-menu .nav-link:hover{ color: #fff; }
        .btn-primary{ background-color: #059669 !important; border-color: #059669 !important; }
        .btn-primary:hover{ background-color: #047857 !important; border-color: #047857 !important; }
    </style>
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
                        accent: { 400: '#FBBF24', 500: '#F59E0B', 600: '#D97706' },
                    },
                },
            },
        };
    </script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>


<body>
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <!-- Navbar Brand-->
    <a class="navbar-brand ps-3" href="index.html">DASHBOARD</a>
    <!-- Sidebar Toggle-->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
    <!-- Navbar Search-->
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
        <div class="input-group">
            <input class="form-control" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
            <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
        </div>
    </form>
    <!-- Navbar-->
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li><a class="dropdown-item" href="#!">Settings</a></li>
                <li><a class="dropdown-item" href="#!">Activity Log</a></li>
                <li><hr class="dropdown-divider" /></li>
                <li><a class="dropdown-item" href="#!">Logout</a></li>
            </ul>
        </li>
    </ul>
</nav>
<div id="layoutSidenav">
    <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
            <div class="sb-sidenav-menu">
                <div class="nav">
                    <div class="sb-sidenav-menu-heading">Core</div>
                    <a class="nav-link" href="{{url('admin/analysis')}}">
                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                        Dashboard
                    </a>
                    <div class="sb-sidenav-menu-heading">Interface</div>


                    <!-- Categories Dropdown -->
                    <a class="nav-link collapsed" href="/admin/view-users" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                        <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                        Users
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>



                    <div class="sb-sidenav-menu-heading">Indoors</div>

                    @unless ($allIndoors->isEmpty())
                        <div class="mt-2 relative z-10">
                            <ul class="mt-2 mx-auto max-w-xs text-left font-small text-sm leading-none border-blue-200 divide-y divide-blue-200">
                        @foreach ($allIndoors as $allIndoor)
                                    <li>
                                        <a href="/admin/indoors/{{$allIndoor['id']}}" class="mb-4 py-3.5 w-full flex items-center text-brand-600 hover:text-brand-700 hover:bg-brand-50">
                                            <span class="ml-5 mr-2.5 w-1 h-7 bg-brand-500 rounded-r-md"></span>
                                            {{$allIndoor->title}}
                                        </a>
                                    </li>
                        @endforeach
                            </ul>

                        </div>
                    @else
                            <a class="py-3.5 w-full flex items-center text-brand-600 hover:text-brand-700 hover:bg-brand-50" href="#">
                                <span class="ml-5 mr-2.5 w-1 h-7 bg-brand-500 rounded-r-md"></span>
                               You have no Indoors yet.
                            </a>

                    @endunless






                </div>
            </div>
            <div class="sb-sidenav-footer">
                <div class="small">Logged in as: {{auth()->user()->name}}</div>

            </div>
        </nav>
    </div>
    <div id="layoutSidenav_content">


        <main>

            @yield('content')


        </main>

    </div>
</div>

<script src="{{asset('assets/js/jquery-3.6.0.min.js')}}" ></script>
<script src="{{asset('assets/js/bootstrap.bundle.min.js')}}" ></script>
<script src="{{asset('assets/js/js/scripts.js')}}" ></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<script src="assets/demo/chart-area-demo.js"></script>
<script src="assets/demo/chart-bar-demo.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="js/datatables-simple-demo.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
    $(document).ready(function() {
        $("#my_summernote").summernote({
            height:250,
        });
        $('.dropdown-toggle').dropdown();
    });
</script>
</body>


</html>
