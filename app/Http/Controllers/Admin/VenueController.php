<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(Request $request): View
    {
        $venues = Venue::with('owner.vendorProfile')
            ->withCount(['services', 'bookings'])
            ->when($request->filled('q'), fn ($q) => $q->search($request->input('q')))
            ->when($request->input('status') === 'pending', fn ($q) => $q->where('is_approved', false))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.venues.index', ['venues' => $venues, 'filters' => $request->only(['q', 'status'])]);
    }

    public function toggleApproval(Venue $venue): RedirectResponse
    {
        $venue->update(['is_approved' => ! $venue->is_approved]);

        return back()->with('message', $venue->is_approved ? "{$venue->name} is now live." : "{$venue->name} has been hidden from customers.");
    }

    public function toggleFeatured(Venue $venue): RedirectResponse
    {
        $venue->update(['is_featured' => ! $venue->is_featured]);

        return back()->with('message', $venue->is_featured ? "{$venue->name} is now featured." : "{$venue->name} removed from featured.");
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        $venue->delete();

        return back()->with('message', 'Venue deleted.');
    }
}
