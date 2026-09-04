<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingUnavailableException;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Indoor;
use App\Models\Resource;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService)
    {
    }

    /** POST /home/{indoors}/book and POST /client/{indoors}/book */
    public function store(StoreBookingRequest $request, Indoor $indoors)
    {
        $resource = Resource::whereKey($request->integer('resource_id'))
            ->where('indoor_id', $indoors->id)
            ->firstOrFail();

        try {
            $this->bookingService->create($resource, $request->user(), $request->validated());
        } catch (BookingUnavailableException $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect('/')->with('message', 'Booking created successfully!');
    }

    /** Owner dashboard route (/client/{indoors}/book): same validation, same flow. */
    public function storeForOwner(StoreBookingRequest $request, Indoor $indoors)
    {
        return $this->store($request, $indoors);
    }

    public function cancel(Request $request, int $booking)
    {
        $booking = Booking::findOrFail($booking);

        $this->bookingService->cancel($booking, $request->user());

        return back()->with('message', 'Booking cancelled successfully!');
    }

    /** FullCalendar feed, scoped to one resource. */
    public function availability(Resource $resource)
    {
        $events = $resource->bookings()
            ->where('status', '!=', 'cancelled')
            ->get()
            ->map(fn ($booking) => [
                'title' => 'Booked',
                'start' => $booking->start_time->toIso8601String(),
                'end' => $booking->finish_time->toIso8601String(),
            ]);

        return response()->json($events);
    }
}
