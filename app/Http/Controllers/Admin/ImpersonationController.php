<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Impersonation;
use Illuminate\Http\RedirectResponse;

class ImpersonationController extends Controller
{
    public function stop(): RedirectResponse
    {
        Impersonation::stop();

        return redirect()->route('filament.admin.resources.vendors.index');
    }
}
