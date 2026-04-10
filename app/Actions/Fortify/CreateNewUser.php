<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $invitationId = session('invitation');
        $invitation = Invitation::find($invitationId);

        if (! $invitation || ! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'email' => ['Your invitation has expired or is invalid.'],
            ]);
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
                Rule::in([$invitation->email]),
            ],
            'password' => $this->passwordRules(),
            'token' => ['required', 'string', Rule::in([$invitation->token])],
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'is_admin' => false,
        ]);

        $user->markEmailAsVerified();
        $invitation->accept();

        session()->forget('invitation');

        return $user;
    }
}
