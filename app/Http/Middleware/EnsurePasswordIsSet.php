<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsSet
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->mustSetPassword() && ! $request->routeIs('password.setup*')) {
            return redirect()->route('password.setup');
        }

        return $next($request);
    }
}
