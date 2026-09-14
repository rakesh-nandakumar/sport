<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorProfile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves the private KYC documents (BR certificate, NIC copy) to staff only.
 * Linked from the vendor review page in the admin panel.
 */
class VendorDocumentController extends Controller
{
    public function __invoke(VendorProfile $vendorProfile, string $type): StreamedResponse
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
