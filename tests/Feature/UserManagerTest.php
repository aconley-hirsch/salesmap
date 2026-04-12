<?php

use App\Livewire\Admin\UserManager;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

test('user manager requires admin', function () {
    $this->get(route('admin.users.index'))
        ->assertRedirect();
});

test('admin can see user manager', function () {
    Livewire::actingAs($this->admin)
        ->test(UserManager::class)
        ->assertSuccessful()
        ->assertSee('Users');
});

test('admin can create a user and gets a setup URL', function () {
    Livewire::actingAs($this->admin)
        ->test(UserManager::class)
        ->set('name', 'New User')
        ->set('email', 'newuser@hirschsecure.com')
        ->call('createUser')
        ->assertSet('setupUrlUserName', 'New User')
        ->assertNotSet('setupUrl', null);

    $user = User::where('email', 'newuser@hirschsecure.com')->first();
    expect($user)->not->toBeNull();
    expect($user->password_set_at)->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();
});

test('signed URL lets a new user access the set-password page', function () {
    $user = User::factory()->create([
        'is_admin' => true,
        'password_set_at' => null,
    ]);

    $url = URL::signedRoute('password.setup', ['user' => $user->id]);

    $this->get($url)
        ->assertOk()
        ->assertSee('Set Your Password');
});

test('invalid signature is rejected', function () {
    $user = User::factory()->create(['password_set_at' => null]);

    $this->get(route('password.setup', ['user' => $user->id, 'signature' => 'invalid']))
        ->assertForbidden();
});

test('new user is redirected to set-password after login', function () {
    $user = User::factory()->create([
        'is_admin' => true,
        'password_set_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('admin.territory-map.edit'))
        ->assertRedirect(route('password.setup'));
});

test('user can set their password', function () {
    $user = User::factory()->create([
        'is_admin' => true,
        'password_set_at' => null,
    ]);

    $this->actingAs($user)
        ->post(route('password.setup.store'), [
            'password' => 'newsecurepassword',
            'password_confirmation' => 'newsecurepassword',
        ])
        ->assertRedirect(route('admin.territory-map.edit'));

    expect($user->fresh()->password_set_at)->not->toBeNull();
});

test('user who already set password is redirected away from set-password page', function () {
    $this->actingAs($this->admin)
        ->get(route('password.setup'))
        ->assertRedirect(route('admin.territory-map.edit'));
});

test('admin can reset another users password and gets a new setup URL', function () {
    $other = User::factory()->create(['password_set_at' => now()]);

    Livewire::actingAs($this->admin)
        ->test(UserManager::class)
        ->call('resetPassword', $other->id)
        ->assertNotSet('setupUrl', null);

    expect($other->fresh()->password_set_at)->toBeNull();
});

test('admin cannot delete themselves', function () {
    Livewire::actingAs($this->admin)
        ->test(UserManager::class)
        ->call('deleteUser', $this->admin->id);

    expect(User::find($this->admin->id))->not->toBeNull();
});
