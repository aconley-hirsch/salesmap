<?php

use App\Models\Invitation;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen requires a valid invitation token', function () {
    $this->get(route('register'))
        ->assertRedirect(route('login'));
});

test('registration screen renders with a valid invitation token', function () {
    $inviter = User::factory()->create();
    $invitation = Invitation::create([
        'email' => 'invitee@example.com',
        'name' => 'Invitee Name',
        'invited_by' => $inviter->id,
    ]);

    $this->get(route('register', ['token' => $invitation->token]))
        ->assertOk()
        ->assertSee('Invitee Name')
        ->assertSee('invitee@example.com');
});

test('an invited user can register', function () {
    $inviter = User::factory()->create();
    $invitation = Invitation::create([
        'email' => 'invitee@example.com',
        'name' => 'Invitee Name',
        'invited_by' => $inviter->id,
    ]);

    // Hit the GET first so the middleware stores the invitation in session
    $this->get(route('register', ['token' => $invitation->token]));

    $response = $this->post(route('register.store'), [
        'name' => 'Invitee Name',
        'email' => 'invitee@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'token' => $invitation->token,
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    expect($invitation->fresh()->isAccepted())->toBeTrue();
});

test('registration rejects an email that does not match the invitation', function () {
    $inviter = User::factory()->create();
    $invitation = Invitation::create([
        'email' => 'invitee@example.com',
        'name' => 'Invitee Name',
        'invited_by' => $inviter->id,
    ]);

    $this->get(route('register', ['token' => $invitation->token]));

    $this->post(route('register.store'), [
        'name' => 'Invitee Name',
        'email' => 'wrong@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'token' => $invitation->token,
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
