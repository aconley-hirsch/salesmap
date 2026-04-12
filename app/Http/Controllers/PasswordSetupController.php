<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordSetupController extends Controller
{
    public function show(Request $request)
    {
        // Signed URL flow — authenticate the user from the signature
        if ($request->hasValidSignature() && $request->has('user')) {
            $user = User::findOrFail($request->query('user'));

            if ($user->mustSetPassword()) {
                Auth::login($user);

                return view('pages.auth.set-password');
            }
        }

        // Already authenticated flow (e.g. admin redirected by middleware)
        if (auth()->check()) {
            if (! auth()->user()->mustSetPassword()) {
                return redirect()->route('admin.territory-map.edit');
            }

            return view('pages.auth.set-password');
        }

        abort(403);
    }

    public function store(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
            'password_set_at' => now(),
        ]);

        return redirect()->route('admin.territory-map.edit');
    }
}
