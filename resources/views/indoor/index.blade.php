@extends('layout')
@section('content')
@include('partials._hero')
@include('partials._activities-showcase')


{{-- Featured indoors section --}}
<section id="indoors" class="mx-auto max-w-screen-xl px-5 pt-16 pb-4">
    <div class="mb-10 text-center">
        <span class="eyebrow">Handpicked for you</span>
        <h2 class="wrappermain">Featured Indoors</h2>
        <p class="section-subtext">Top-rated venues, ready to book in seconds.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3">
        @foreach ($indoors as $item)
            <x-indoors-cards :item="$item"/>
        @endforeach
    </div>
</section>



<section class="parallax-cta bg-cover bg-center bg-no-repeat bg-fixed" style="background-image:url(/images/parallaxfinal.jpg)">
    <h2 class="relative font-display text-4xl font-bold tracking-widest text-white sm:text-5xl md:text-6xl xl:text-7xl">
        <span class="text-accent-400">ENDURE</span> : STRIVE : <span class="text-accent-400">CONQUER</span>
    </h2>
</section>


<section id="tournaments" class="mx-auto max-w-screen-xl px-5 py-16">
    <div class="mb-10 text-center">
        <span class="eyebrow">Compete &amp; connect</span>
        <h2 class="wrappermain">Upcoming Events</h2>
        <p class="section-subtext">Jump into a tournament near you &mdash; casual or competitive.</p>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-4">
        @foreach($tournaments as $tourn)
            <x-tournaments-cards :tourn="$tourn"/>
        @endforeach
    </div>
</section>




@include('partials._banner')

<!--our services-->
<div class="container3">
    <div class="servicesontainer">
        <div class="mb-2 text-center">
            <span class="eyebrow">Why facilities choose us</span>
        </div>
        <h1 class="heading">Our Service</h1>
        <div class="services">
            <div class="card">
                <span class="card-icon"><i class="fa-solid fa-hands-clapping"></i></span>
                <h2>No more chasing payments</h2>
                <p>Automatic, secure online payments, straight into your Indoor Facility's bank account.</p>

            </div>

            <div class="card">
                <span class="card-icon"><i class="fa-regular fa-clock"></i></span>
                <h2>Available 24/7, on any device</h2>
                <p>Smart scheduling and calendar overview, to save you time.</p>

            </div>

            <div class="card">
                <span class="card-icon"><i class="fa-regular fa-credit-card"></i></span>
                <h2>Want more bookings and less admin?</h2>
                <p>Bring your facility booking online, to 1,000s of users who can book instantly.</p>

            </div>
        </div>
        </div>


    </div>






<section id="testimonies" class="py-10 bg-slate-900">
    <div class="max-w-6xl mx-8 md:mx-10 lg:mx-20 xl:mx-auto">


        <div class="transition duration-500 ease-in-out transform scale-100 translate-x-0 translate-y-0 opacity-100">
            <div class="mb-12 space-y-5 md:mb-16 md:text-center">
                <span class="eyebrow eyebrow--light block">Built for every kind of venue</span>
                <h1 class="mb-5 text-3xl font-semibold text-white md:text-center md:text-5xl">
                   Features we offer for Indoors
                </h1>
                <p class="text-xl text-gray-100 md:text-center md:text-2xl">
                    Tools for completing the task
                </p>
            </div>
        </div>


        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">


            <ul class="space-y-8">


                <li class="text-sm leading-6">
                    <div class="relative group">
                        <div  class="absolute transition rounded-lg opacity-25 -inset-1 bg-gradient-to-r from-brand-500 to-accent-500 blur duration-400 group-hover:opacity-100 group-hover:duration-200">
                        </div>
                            <div class="relative p-6 space-y-6 leading-none rounded-lg bg-slate-800 ring-1 ring-gray-900/5">
                                <div class="flex items-center space-x-4  font-semibold text-indigo-100 ">
                                    <i class="fa-solid fa-database "></i>
                                    <div>
                                        <h3 class="text-lg font-semibold text-white">Set Availability</h3>
                                    </div>
                                </div>
                                <p class="leading-normal text-gray-300 text-md">Your schedule is entirely up to you; you can include closures, internal usage, maintenance, and more.</p>
                            </div>
                        </a>
                    </div>
                </li>



                <li class="text-sm leading-6">
                    <div class="relative group">
                        <div  class="absolute transition rounded-lg opacity-25 -inset-1 bg-gradient-to-r from-brand-500 to-accent-500 blur duration-400 group-hover:opacity-100 group-hover:duration-200">
                        </div>
                            <div class="relative p-6 space-y-6 leading-none rounded-lg bg-slate-800 ring-1 ring-gray-900/5">
                                <div class="flex items-center space-x-4  font-semibold text-indigo-100 ">
                                    <i class="fa-solid fa-bookmark"></i>
                                    <div>
                                        <h3 class="text-lg font-semibold text-white">Offline reservations</h3>
                                    </div>
                                </div>
                                <p class="leading-normal text-gray-300 text-md">Made to handle reservations over the phone, via email, and in person.</p>
                            </div>
                        </a>
                    </div>
                </li>



                <li class="text-sm leading-6">
                    <div class="relative group">
                        <div  class="absolute transition rounded-lg opacity-25 -inset-1 bg-gradient-to-r from-brand-500 to-accent-500 blur duration-400 group-hover:opacity-100 group-hover:duration-200">
                        </div>
                            <div class="relative p-6 space-y-6 leading-none rounded-lg bg-slate-800 ring-1 ring-gray-900/5">
                                <div class="flex items-center space-x-4  font-semibold text-indigo-100 ">
                                   <i class="fa-solid fa-mobile"></i>
                                    <div>
                                        <h3 class="text-lg font-semibold text-white">Multi Device</h3>
                                    </div>
                                </div>
                                <p class="leading-normal text-gray-300 text-md">Grounds teams and other staff can access any device at any time, from any location.</p>
                            </div>
                        </a>
                    </div>
                </li>


                <li class="text-sm leading-6">
                    <div class="relative group">
                        <div  class="absolute transition rounded-lg opacity-25 -inset-1 bg-gradient-to-r from-brand-500 to-accent-500 blur duration-400 group-hover:opacity-100 group-hover:duration-200">
                        </div>
                            <div class="relative p-6 space-y-6 leading-none rounded-lg bg-slate-800 ring-1 ring-gray-900/5">
                                <div class="flex items-center space-x-4  font-semibold text-indigo-100 ">
                                    <i class="fa-solid fa-clock"></i>
                                    <div>
                                        <h3 class="text-lg font-semibold text-white">24/7</h3>
                                    </div>
                                </div>
                                <p class="leading-normal text-gray-300 text-md">Online reservations available 24/7, so there's no need to have someone "on call" all the time</p>
                            </div>
                        </a>
                    </div>
                </li>

            </ul>


            <ul class="hidden space-y-8 sm:block">
                <li class="text-sm leading-6">
                    <div class="relative group">
                        <div  class="absolute transition rounded-lg opacity-25 -inset-1 bg-gradient-to-r from-brand-500 to-accent-500 blur duration-400 group-hover:opacity-100 group-hover:duration-200">
                        </div>
                            <div class="relative p-6 space-y-6 leading-none rounded-lg bg-slate-800 ring-1 ring-gray-900/5">
                                <div class="flex items-center space-x-4  font-semibold text-indigo-100 ">
                                    <i class="fa-solid fa-user-group"></i>
                                    <div>
                                        <h3 class="text-lg font-semibold text-white"> User-Friendly Interface</h3>
                                    </div>
                                </div>
                                <p class="leading-normal text-gray-300 text-md">Navigating through our platform is a breeze with a user-friendly interface. Intuitive design and clear options make it easy for both staff and users to interact with the system, promoting efficiency and a positive experience.</p>
                            </div>
                        </a>
                    </div>
                </li>

    <li class="text-sm leading-6">
        <div class="relative group">
            <div  class="absolute transition rounded-lg opacity-25 -inset-1 bg-gradient-to-r from-brand-500 to-accent-500 blur duration-400 group-hover:opacity-100 group-hover:duration-200">
            </div>
                <div class="relative p-6 space-y-6 leading-none rounded-lg bg-slate-800 ring-1 ring-gray-900/5">
                    <div class="flex items-center space-x-4  font-semibold text-indigo-100 ">
                        <i class="fa-brands fa-watchman-monitoring"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-white"> Booking History Tracking:</h3>
                        </div>
                    </div>
                    <p class="leading-normal text-gray-300 text-md">
                        Keep a comprehensive record with booking history tracking. Our system logs all reservations, modifications, and cancellations, providing a detailed history that can be useful for analysis, reporting, and ensuring accountability.</p>
                </div>
            </a>
        </div>
    </li>

    <li class="text-sm leading-6">
        <div class="relative group">
            <div  class="absolute transition rounded-lg opacity-25 -inset-1 bg-gradient-to-r from-brand-500 to-accent-500 blur duration-400 group-hover:opacity-100 group-hover:duration-200">
            </div>
                <div class="relative p-6 space-y-6 leading-none rounded-lg bg-slate-800 ring-1 ring-gray-900/5">
                    <div class="flex items-center space-x-4  font-semibold text-indigo-100 ">
                        <i class="fa-solid fa-fire"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Special Offers</h3>
                        </div>
                    </div>
                    <p class="leading-normal text-gray-300 text-md">Boost engagement and attract more users by leveraging our Special Offers integration. Easily create and manage promotional deals, discounts, and special packages to enhance the appeal of your venue, keeping your user base excited and engaged.</p>
                </div>
            </a>
        </div>
    </li>

  <li class="text-sm leading-6">
        <div class="relative group">
            <div  class="absolute transition rounded-lg opacity-25 -inset-1 bg-gradient-to-r from-brand-500 to-accent-500 blur duration-400 group-hover:opacity-100 group-hover:duration-200">
            </div>
                <div class="relative p-6 space-y-6 leading-none rounded-lg bg-slate-800 ring-1 ring-gray-900/5">
                    <div class="flex items-center space-x-4  font-semibold text-indigo-100 ">
                        <i class="fa-solid fa-futbol"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Tournament Management</h3>
                        </div>
                    </div>
                    <p class="leading-normal text-gray-300 text-md">Elevate the competitive spirit with our Tournament Management feature. Operators can effortlessly publish, update, and organize tournaments within the platform, providing a comprehensive solution for both casual and professional players.</p>
                </div>
            </a>
        </div>
    </li>
            </ul>


            <ul class="hidden space-y-8 lg:block">


                 <li class="text-sm leading-6">
        <div class="relative group">
            <div  class="absolute transition rounded-lg opacity-25 -inset-1 bg-gradient-to-r from-brand-500 to-accent-500 blur duration-400 group-hover:opacity-100 group-hover:duration-200">
            </div>
                <div class="relative p-6 space-y-6 leading-none rounded-lg bg-slate-800 ring-1 ring-gray-900/5">
                    <div class="flex items-center space-x-4  font-semibold text-indigo-100 ">
                        <i class="fa-solid fa-phone-volume"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Customer communication</h3>
                        </div>
                    </div>
                    <p class="leading-normal text-gray-300 text-md">Forge meaningful connections with your customers through our integrated communication hub. From sending personalized notifications to addressing inquiries and feedback, our platform serves as a central hub for fostering communication and building strong customer relationship</p>
                </div>
            </a>
        </div>
    </li>

    <li class="text-sm leading-6">
        <div class="relative group">
            <div  class="absolute transition rounded-lg opacity-25 -inset-1 bg-gradient-to-r from-brand-500 to-accent-500 blur duration-400 group-hover:opacity-100 group-hover:duration-200">
            </div>
                <div class="relative p-6 space-y-6 leading-none rounded-lg bg-slate-800 ring-1 ring-gray-900/5">
                    <div class="flex items-center space-x-4  font-semibold text-indigo-100 ">
                        <i class="fa-solid fa-handshake-angle"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Help and Support Center</h3>
                        </div>
                    </div>
                    <p class="leading-normal text-gray-300 text-md">Empower users with a dedicated Help and Support Center. Whether it's clarifying doubts, providing guidance on reservations, or troubleshooting issues, our platform includes robust support features to ensure users have access to assistance whenever needed.</p>
                </div>
            </a>
        </div>
    </li>

                <li class="text-sm leading-6">
        <div class="relative group">
            <div  class="absolute transition rounded-lg opacity-25 -inset-1 bg-gradient-to-r from-brand-500 to-accent-500 blur duration-400 group-hover:opacity-100 group-hover:duration-200">
            </div>
                <div class="relative p-6 space-y-6 leading-none rounded-lg bg-slate-800 ring-1 ring-gray-900/5">
                    <div class="flex items-center space-x-4  font-semibold text-indigo-100 ">
                        <i class="fa-solid fa-bottle-water"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Refreshment Add-ons</h3>
                        </div>
                    </div>
                    <p class="leading-normal text-gray-300 text-md">Enhance the overall customer experience with convenient refreshment add-ons. Our system allows operators to offer and manage additional services, such as refreshments, creating a more enjoyable and comprehensive experience for users during their visit.</p>
                </div>
            </a>
        </div>
    </li>


            </ul>


        </div>
    </div>
</section>


@include('partials._testimonials')





{{-- Our partners section  --}}

{{-- <section class=" relative bg-white sm:py-10 lg:py-16 lg:pt-10">


    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8 text-center  ">
        <div class="max-w-2xl mx-auto text-center">
            <h2 class="text-2xl font-light text-black sm:text-4xl sm:leading-tight wrappermain">Trusted by the bests
            </h2>
        </div>

        <div class=" grid items-center max-w-4xl grid-cols-2 gap-4 mx-auto mt-12 md:mt-20 md:grid-cols-4  ">
            <div class="bg-white h-12 flex shadow-lg items-center justify-center">
                <img class="object-contain w-full h-8 mx-auto" src="https://cdn.rareblocks.xyz/collection/celebration/images/logos/3/logo-1.png" alt="">
            </div>

            <div class="bg-white h-12 flex shadow-lg items-center justify-center">
                <img class="object-contain w-full h-8 mx-auto" src="https://cdn.rareblocks.xyz/collection/celebration/images/logos/3/logo-2.png" alt="">
            </div>

            <div class="bg-white h-12 flex shadow-lg items-center justify-center">
                <img class="object-contain w-full h-8 mx-auto" src="https://cdn.rareblocks.xyz/collection/celebration/images/logos/3/logo-3.png" alt="">
            </div>


            <div class="bg-white h-12 flex shadow-lg items-center justify-center">
                <img class="object-contain w-full h-8 mx-auto" src="https://cdn.rareblocks.xyz/collection/celebration/images/logos/3/logo-7.png" alt="">
            </div>

            <div class="bg-white h-12 flex shadow-lg items-center justify-center">
                <img class="object-contain w-full h-8 mx-auto" src="https://cdn.rareblocks.xyz/collection/celebration/images/logos/3/logo-8.png" alt="">
            </div>

            <div class="bg-white h-12 flex shadow-lg items-center justify-center">
                <img class="object-contain w-full h-8 mx-auto" src="https://cdn.rareblocks.xyz/collection/celebration/images/logos/3/logo-9.png" alt="">
            </div>

            <div class="bg-white h-12 flex shadow-lg items-center justify-center">
                <img class="object-contain w-full mx-auto h-7" src="https://cdn.rareblocks.xyz/collection/celebration/images/logos/3/logo-10.png" alt="">
            </div>

            <div class="bg-white h-12 flex shadow-lg items-center justify-center">
                <img class="object-contain w-full h-8 mx-auto" src="https://cdn.rareblocks.xyz/collection/celebration/images/logos/3/logo-11.png" alt="">
            </div>


        </div>

        <div class="flex items-center justify-center mt-10 space-x-3 md:hidden">
            <div class="w-2.5 h-2.5 rounded-full bg-blue-600 block"></div>
            <div class="w-2.5 h-2.5 rounded-full bg-gray-300 block"></div>
            <div class="w-2.5 h-2.5 rounded-full bg-gray-300 block"></div>
        </div>


    </div>


    <div class="mt-16 mb-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl bg-gray-900 py-10 px-6 sm:py-16 sm:px-12 lg:flex lg:items-center lg:p-20">
            <div class="lg:w-0 lg:flex-1">
                <h2 class="text-3xl font-bold tracking-tight text-white">Sign up for our newsletter</h2>
                <p class="mt-4 max-w-3xl text-lg text-gray-100">
                    Join our community of indoor enthusiasts! Subscribe to receive exclusive updates on our indoor facilities, special promotions, tips for maximizing your indoor experiences, and much more.
                </p>
            </div>
            <div class="mt-12 sm:w-full sm:max-w-md lg:mt-0 lg:ml-8 lg:flex-1">
                <form action="/subscribe" method="POST" class="sm:flex space-y-2">
                    @csrf
                    <label for="email-address" class="sr-only">Email address</label>

                    <input id="email-address"  name="email" type="email" autocomplete="email" class="w-full rounded-md border-white px-5 py-3 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-700" placeholder="Your email">

                    <button type="submit" class="mt-3 flex w-full items-center justify-center rounded-md border border-transparent bg-gray-500 px-5 py-3 text-base font-medium text-white hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-700 sm:mt-0 sm:ml-3 sm:w-auto sm:flex-shrink-0">Subscribe</button>
                </form>
                <p class="mt-3 text-sm text-gray-100">
                    We care about the protection of your data. Read our
                    <a href="" class="font-medium text-white underline">Privacy Policy.</a>
                </p>
            </div>
        </div>
    </div>
</section> --}}





@endsection
