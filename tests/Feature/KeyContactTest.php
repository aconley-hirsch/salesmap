<?php

use App\Livewire\Admin\KeyContactManager;
use App\Models\KeyContact;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

test('public key contacts page renders', function () {
    $this->get(route('key-contacts'))
        ->assertSuccessful();
});

test('public key contacts page shows active contacts grouped', function () {
    KeyContact::factory()->create(['name' => 'Jane Leader', 'group_name' => 'Leaders', 'is_active' => true]);
    KeyContact::factory()->create(['name' => 'Hidden Person', 'group_name' => 'Leaders', 'is_active' => false]);

    $this->get(route('key-contacts'))
        ->assertSee('Jane Leader')
        ->assertDontSee('Hidden Person');
});

test('admin key contacts page requires admin auth', function () {
    $this->get(route('admin.key-contacts.index'))
        ->assertRedirect();
});

test('admin key contacts page renders for admin', function () {
    Livewire::actingAs($this->admin)
        ->test(KeyContactManager::class)
        ->assertSuccessful()
        ->assertSee('Key Contacts');
});

test('admin can create a key contact', function () {
    Livewire::actingAs($this->admin)
        ->test(KeyContactManager::class)
        ->call('create')
        ->set('form.name', 'New Person')
        ->set('form.title', 'Director')
        ->set('form.email', 'new@hirschsecure.com')
        ->set('form.group_name', 'Leaders')
        ->call('save');

    expect(KeyContact::where('name', 'New Person')->exists())->toBeTrue();
});

test('admin can edit a key contact', function () {
    $contact = KeyContact::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($this->admin)
        ->test(KeyContactManager::class)
        ->call('edit', $contact->id)
        ->set('form.name', 'New Name')
        ->call('save');

    expect($contact->fresh()->name)->toBe('New Name');
});

test('admin can delete a key contact', function () {
    $contact = KeyContact::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(KeyContactManager::class)
        ->call('delete', $contact->id);

    expect(KeyContact::find($contact->id))->toBeNull();
});

test('create validates required fields', function () {
    Livewire::actingAs($this->admin)
        ->test(KeyContactManager::class)
        ->call('create')
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors('form.name');
});

test('shared inboxes render as pills on public page', function () {
    KeyContact::factory()->create([
        'name' => 'Sales',
        'email' => 'sales@hirschsecure.com',
        'group_name' => 'Shared Inboxes',
        'group_order' => 3,
    ]);

    $this->get(route('key-contacts'))
        ->assertSee('sales@hirschsecure.com');
});
