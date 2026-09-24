<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleValidationMiddleware
{
    /**
     * Usage: ->middleware('role:Vendor') or ->middleware('role:Vendor,SuperAdministrator').
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = array_filter(array_map(fn ($r) => Role::fromKey(trim($r)), $roles));
        abort_if(empty($allowed), 500, 'No valid role supplied to the role middleware.');

        if (! $request->user()) {
            return redirect()->guest(route('login'));
        }

        if (! $request->user()->hasRole(...$allowed)) {
            abort(403, 'You do not have access to this area.');
        }

        return $next($request);
    }
}
