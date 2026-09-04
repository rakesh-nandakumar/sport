@extends('dashboard-layout')
@section('content')

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="container-fluid px-4">
        <h1 class="mt-4">Analysis</h1>

        @if (session('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif

        <div class="row">
            <div class="col-md-3">
                <div class="card card-body bg-primary text-white mb-3">
                    <label for="">Total Indoors</label>
                    <h1>{{ $totalIndoors }}</h1>

                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-body bg-info text-white mb-3">
                    <label for="">Total Indoor Operators</label>
                    <h1>{{ $totalUsers }}</h1>

                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-body bg-warning text-white mb-3">
                    <label for="">Total Users</label>
                    <h1>{{ $totalCustomers }}</h1>

                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-body bg-danger text-white mb-3">
                    <label for="">Total Bookings</label>
                    <h1>{{ $totalbookings }}</h1>

                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-body bg-secondary text-white mb-3">
                    <label for="">Total Tournaments</label>
                    <h1>{{ $totalTournaments }}</h1>

                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-body bg-success text-white mb-3">
                    <label for="">Today Bookings</label>
                    <h1>{{ $todayBookings }}</h1>

                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-body bg-dark text-white mb-3">
                    <label for="">Month Bookings</label>
                    <h1>{{ $monthlyBookings }}</h1>

                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-body bg-info text-white mb-3">
                    <label for="">Total Active customers</label>
                    <h1>{{ $custCount }}</h1>

                </div>
            </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        Total Bookings Over Time
                    </div>
                    <div class="card-body">
                        <canvas id="bookingChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        Total Customers Over Time
                    </div>
                    <div class="card-body">
                        <canvas id="customerChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                Customer Growth by Month
            </div>
            <div class="card-body">
                <canvas id="chart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Ensure that Chart.js is initialized after the DOM has fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Bar Chart for Customers Growth by Month
            var ctx = document.getElementById('chart').getContext('2d');
            var userChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($labels) !!},
                    datasets: {!! json_encode($datasets) !!}
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Line Chart for Total Bookings
            var bookingCtx = document.getElementById('bookingChart').getContext('2d');
            var bookingChart = new Chart(bookingCtx, {
                type: 'line',
                data: {
                    labels: @json($bookingLabels),
                    datasets: [{
                        label: 'Total Bookings',
                        data: @json($bookingValues),
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Line Chart for Total Customers
            var customerCtx = document.getElementById('customerChart').getContext('2d');
            var customerChart = new Chart(customerCtx, {
                type: 'line',
                data: {
                    labels: @json($customerLabels),
                    datasets: [{
                        label: 'Total Customers',
                        data: @json($customerValues),
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>

@endsection
