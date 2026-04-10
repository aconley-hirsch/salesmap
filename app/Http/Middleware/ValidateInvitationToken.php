<?php

namespace App\Http\Middleware;

use App\Models\Invitation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateInvitationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('token');

        if (! $token) {
            return redirect()->route('login')
                ->with('error', 'Valid invitation required to register.');
        }

        $invitation = Invitation::where('token', $token)->first();

        if (! $invitation || ! $invitation->isPending()) {
            return redirect()->route('login')
                ->with('error', 'This invitation is invalid or has expired.');
        }

        session(['invitation' => $invitation->id]);

        return $next($request);
    }
}
