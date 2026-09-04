@extends('user-dashboard-layout')
@section('content')




    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <section class="container mx-auto p-6 font-mono">
        <header class="flex justify-between items-center mb-6">
            <h1 class="text-3xl text-center font-bold uppercase">
               {{$place  -> title}}
            </h1>

        </header>



        <div class="container-fluid px-4">
            <h1 class="mt-4">Analysis</h1>

            @if (session('message'))
                <div class="alert alert-success">{{ session('message')}}</div>
            @endif

            <div class="row">
                <div class="col-md-3">
                    <div class="card card-body bg-primary text-white mb-3">
                        <label for="">Total Booking</label>
                        <h1> {{$totalBooking}}</h1>

                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-body bg-primary text-white mb-3">
                        <label for="">Total Booking this week</label>
                        <h1> {{$totalBookingThisWeek}}</h1>
                    </div>
                </div>

                <div class="col-md-3">

                    <div class="card card-body bg-primary text-white mb-3">
                        <label for="">Total Booking this month</label>
                        <h1> {{$totalBookingThisMonth}}</h1>
                    </div>

                </div>




                <div class="col-md-3">


                    <div class="card card-body bg-primary text-white mb-3">
                        <label for="">Total Today</label>
                        <h1> {{$totalBookingToday}}</h1>
                    </div>
                </div>

            </div>


            </div>




        <div class="card mb-4">

        </div>
        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-1"></i>
                        Bar Chart Example
                    </div>
                    <div class="card-body"><canvas id="myBarChart" width="100%" height="50"></canvas></div>
                    <div class="card-footer small text-muted">Updated yesterday at 11:59 PM</div>
                </div>
            </div>

        </div>




        <div class="my-4">
            <form method="GET">
                <select name="resource" onchange="this.form.submit()" class="border border-gray-200 rounded p-2 bg-white">
                    <option value="">All units</option>
                    @foreach ($resources as $resource)
                        <option value="{{ $resource->id }}" @selected(request('resource') == $resource->id)>
                            {{ $resource->name }} ({{ $resource->activity?->name }})
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="w-full mb-8 overflow-hidden rounded-lg shadow-lg">
            <div class="w-full overflow-x-auto">
                <table class="w-full">
                    <thead>
                    <tr class="text-md font-semibold tracking-wide text-left text-gray-900 bg-gray-100 uppercase border-b border-gray-600">
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Start and End time</th>
                        <th class="px-4 py-3">Duration</th>
                        <th class="px-4 py-3">Unit</th>
                        <th class="px-4 py-3">Activity</th>
                        <th class="px-4 py-3">Game / Option</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Phone Number</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white">
                    @unless ($bookings->isEmpty())
                        @foreach ($bookings as $booking)
                            @php
                                $durationMinutes = $booking->start_time->diffInMinutes($booking->finish_time);
                                $picks = array_filter($booking->selected_options ?? []);
                            @endphp

                            <tr class="text-gray-700">
                                <td class="px-4 py-3 border">
                                    {{
       $booking->start_time->isToday() ? 'Today' :
       ($booking->start_time->isTomorrow() ? 'Tomorrow' :
       $booking->start_time->format('d-m-y'))
   }}
                                </td>

                                <td class="px-4 py-3 border">
                                        {{ $booking->start_time->format('H:i') }}
                                      -
                                        {{ $booking->finish_time->format('H:i') }}
                                </td>

                                <td class="px-4 py-3 border">
                                    @if ($durationMinutes >= 60)
                                        {{ intdiv($durationMinutes, 60) }}h{{ $durationMinutes % 60 ? ' ' . $durationMinutes % 60 . 'm' : '' }}
                                    @else
                                        {{ $durationMinutes }}m
                                    @endif
                                </td>

                                <td class="px-4 py-3 border">
                                    {{ $booking->resource?->name ?? '—' }}
                                </td>

                                <td class="px-4 py-3 border">
                                    {{ $booking->resource?->activity?->name ?? '—' }}
                                </td>

                                <td class="px-4 py-3 border">
                                    {{ implode(', ', $picks) ?: '—' }}
                                </td>

                                <td class="px-4 py-3 border">
                                    {{ $booking->custName }}
                                </td>

                                <td class="px-4 py-3 border">
                                    {{ $booking->phoneNumber }}
                                </td>

                                <td class="px-4 py-3 border">
                                    {{ $booking->total_price !== null ? 'Rs ' . number_format($booking->total_price, 2) : '—' }}
                                </td>

                                <td class="px-4 py-3 border">
                                    <span class="px-2 py-1 font-semibold leading-tight rounded-sm
                                        {{ $booking->status === 'cancelled' ? 'text-red-700 bg-red-100' : ($booking->status === 'completed' ? 'text-blue-700 bg-blue-100' : 'text-green-700 bg-green-100') }}">
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 border">
                                    @if ($booking->status !== 'cancelled')
                                        <div class="flex space-x-4">
                                            <form action="{{ route('cancel-booking', $booking->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-red-500 px-3 py-2 rounded hover:bg-red-100">
                                                    <i class="fa-solid fa-ban"></i> Cancel
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="11" class="px-4 py-3 border text-center">No bookings found.</td>
                        </tr>
                    @endunless
                    </tbody>
                </table>
            </div>
        </div>




        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mt-10" style="margin-left: 20px; margin-right: 20px;">
                <div id="calendar"></div>
            </div>
        </div>


        <div class="min-h-screen p-6 flex items-center justify-center mt-20">

            <div class="container max-w-screen-lg mx-auto">
                <form  method="POST" action="/client/{{$place['id']}}/book" enctype="multipart/form-data">
                    @csrf
                    <div>
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <input type="hidden" name="indoor_id" value="{{$place->id}}">
                        <h2 class="font-semibold text-xl text-gray-600">Book time slots</h2>
                        <p class="text-gray-500 mb-6">Form is mobile responsive. Give it a try.</p>

                        <div class="bg-white rounded shadow-lg p-4 px-4 md:p-8 mb-6">
                            <div class="grid gap-4 gap-y-2 text-sm grid-cols-1 lg:grid-cols-3">
                                <div class="text-gray-600">
                                    <p class="font-medium text-lg">Details of the Customer</p>
                                    <p>Please fill out all the fields.</p>
                                </div>

                                <div class="lg:col-span-2">
                                    <div class="grid gap-4 gap-y-2 text-sm grid-cols-1 md:grid-cols-5">
                                        <div class="md:col-span-5">
                                            <label for="resource">Bookable Unit</label>
                                            <select name="resource_id" id="resource" class="h-10 border mt-1 rounded px-4 w-full bg-gray-50">
                                                <option value="">Choose a unit...</option>
                                                @foreach ($resources as $resource)
                                                    <option value="{{ $resource->id }}">{{ $resource->name }} ({{ $resource->activity?->name }} - Rs {{ number_format($resource->rate, 2) }} / {{ $resource->pricing_unit }})</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="md:col-span-5">
                                            <label for="full_name">Full Name</label>
                                            <input type="text" name="custName" id="full_name" class="h-10 border mt-1 rounded px-4 w-full bg-gray-50" value="" />
                                        </div>

                                        <div class="md:col-span-5">
                                            <label for="phone">Phone Number</label>
                                            <input type="text" name="phoneNumber" id="phone" class="h-10 border mt-1 rounded px-4 w-full bg-gray-50" value="" />
                                        </div>

                                        <div class="md:col-span-5">
                                            <label for="start_time">Start Time</label>
                                            <input type="datetime-local" name="start_time" id="start_time" class="h-10 border mt-1 rounded px-4 w-full bg-gray-50" />
                                        </div>

                                        <div class="md:col-span-5">
                                            <label for="finish_time">End Time</label>
                                            <input type="datetime-local" name="finish_time" id="finish_time" class="h-10 border mt-1 rounded px-4 w-full bg-gray-50" />
                                        </div>

                                        <!-- Add more fields as needed -->

                                        <div class="md:col-span-5 text-right">
                                            <div class="inline-flex items-end">
                                                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Submit</button>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>




            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
            <script>

                document.addEventListener('DOMContentLoaded', function () {
                    var calendarEl = document.getElementById('calendar');
                    var calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'timeGridWeek',
                        selectable: true,
                        slotMinTime: '8:00:00',
                        slotMaxTime: '23:59:59',
                        events: @json($events),


                    });

                    calendar.render();
                });
            </script>
        @endpush


    </section>

    <script type="text/javascript">
        var _ydata = JSON.parse('{!! json_encode($months) !!}');
        var _xdata = JSON.parse('{!! json_encode($monthCount) !!}');
    </script>

    <script src="{{asset('assets/js/chart-bar-demo.js')}}" ></script>
    <script src="{{asset('assets/js/chart-area-demo.js')}}" ></script>






@endsection
