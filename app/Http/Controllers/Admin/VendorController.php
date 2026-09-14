<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityType;
use App\Models\VendorProfile;
use App\Notifications\BookingNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Vendor moderation. "Vendors" are the businesses that list venues; every one of them goes through
 * a review before customers can see their listings.
 */
class VendorController extends Controller
{
    public function index(Request $request): View
    {
        $vendors = VendorProfile::with(['user' => fn ($q) => $q->withCount(['venues', 'bookings'])])
            ->status($request->input('status'))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->input('q').'%';
                $q->where(fn ($w) => $w->where('business_name', 'like', $term)
                    ->orWhere('city', 'like', $term)
                    ->orWhere('registration_number', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term)));
            })
            ->orderByRaw("case status when 'pending' then 0 when 'active' then 1 when 'suspended' then 2 else 3 end")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.vendors.index', [
            'vendors' => $vendors,
            'statuses' => VendorStatus::cases(),
            'counts' => VendorProfile::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'filters' => $request->only(['status', 'q']),
        ]);
    }

    public function show(VendorProfile $vendorProfile): View
    {
        $vendorProfile->load(['user.venues' => fn ($q) => $q->withCount(['services', 'bookings']), 'reviewer']);

        return view('admin.vendors.show', [
            'vendor' => $vendorProfile,
            'activityTypes' => ActivityType::whereIn('id', $vendorProfile->activity_type_ids ?? [])->get(),
            'statuses' => VendorStatus::cases(),
        ]);
    }

    /** Activate / suspend / reject with a note the vendor can read on their dashboard. */
    public function updateStatus(Request $request, VendorProfile $vendorProfile): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(VendorStatus::class)],
            'review_notes' => ['nullable', 'string', 'max:1000', Rule::requiredIf(in_array($request->input('status'), ['suspended', 'rejected']))],
        ], [
            'review_notes.required' => 'Tell the vendor why (this note is shown to them).',
        ]);

        $status = VendorStatus::from($data['status']);
        $vendorProfile->update([
            'status' => $status,
            'review_notes' => $data['review_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $message = match ($status) {
            VendorStatus::Active => "Your vendor account for {$vendorProfile->business_name} has been activated. Your venues are now visible to customers.",
            VendorStatus::Suspended => "Your vendor account has been suspended. Your venues are hidden until this is resolved. Note from our team: {$data['review_notes']}",
            VendorStatus::Rejected => "Your vendor application was not approved. Note from our team: {$data['review_notes']}",
            VendorStatus::Pending => 'Your vendor account has been put back into review.',
        };
        $vendorProfile->user->notify(new BookingNotification($message, $status === VendorStatus::Active ? 'success' : 'danger', null, route('vendor.dashboard')));

        return back()->with('message', "{$vendorProfile->business_name} is now {$status->label()}.");
    }

    /** Serves the private KYC documents (BR certificate, NIC copy) to staff only. */
    public function document(VendorProfile $vendorProfile, string $type): StreamedResponse
    {
        $path = match ($type) {
            'br' => $vendorProfile->br_document_path,
            'nic' => $vendorProfile->nic_document_path,
            default => null,
        };
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
