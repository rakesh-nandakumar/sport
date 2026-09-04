<?php

namespace App\Http\Middleware;

use app\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class RoleValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        ?string $role = null
    )
    {

        //Doubt ???????????????
        // Check if the provided role is valid. If not, abort the request with a 400 Bad Request error.
       if (!$role || !\App\Enums\Role::fromKey($role)){
           abort(400, 'Invalid Role');
       }

        // Check if the user is authenticated. If not, redirect to the login route.
        if (!auth()->check()){
            return  redirect()->route('login');
        }


        // Check if the user has a role assigned. If not, redirect to the login route.
        if (!auth()->user()->role_id) {
            return redirect()->route('login');
        }



        // Validate the user's role against the list of valid roles. If the user's role is invalid, redirect to the login route.
        if (!\App\Enums\Role::fromValue(auth()->user()->role_id->value)){
            return redirect()->route('login');
        }


        // Check if the user's role matches the required role for the current request. If not, abort the request with a 403 Forbidden error
        if (auth()->user()->role_id->value !== Role::fromKey($role)->value) {
            abort(403, 'Unauthorized.');
        }

// If all checks pass, proceed with the request.
        return $next($request);

    }
}
