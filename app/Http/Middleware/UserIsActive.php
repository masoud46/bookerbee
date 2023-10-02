<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
		if (Auth::user() &&  Auth::user()->status > 0) { // -1:suspended 0=inactive
			return $next($request);
		}

		// abort(404);
		return redirect()->route("account.profile")->with('error', __('Your account information is not complete!'));
    }
}
