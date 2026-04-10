<?php

use App\Enums\RoleType;
use App\Livewire\Admin\SalesTeamMemberForm;
use App\Livewire\Admin\SalesTeamMemberManager;
use App\Models\SalesTeamMember;
use App\Models\TerritoryAssignment;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

test('sales team manager page requires admin auth', function () {
    $this->get(route('admin.sales-team.index'))
        ->assertRedirect();
});

test('sales team manager lists members', function () {
    $member = SalesTeamMember::factory()->create(['name' => 'Test Person']);

    Livewire::actingAs($this->admin)
        ->test(SalesTeamMemberManager::class)
        ->assertSee('Test Person')
        ->assertSuccessful();
});

test('sales team manager can search by name', function () {
    SalesTeamMember::factory()->create(['name' => 'Alice Smith']);
    SalesTeamMember::factory()->create(['name' => 'Bob Jones']);

    Livewire::actingAs($this->admin)
        ->test(SalesTeamMemberManager::class)
        ->set('search', 'Alice')
        ->assertSee('Alice Smith')
        ->assertDontSee('Bob Jones');
});

test('sales team manager can filter by role', function () {
    $rsmMember = SalesTeamMember::factory()->create(['name' => 'RSM Person']);
    $seMember = SalesTeamMember::factory()->create(['name' => 'SE Person']);

    TerritoryAssignment::factory()->create([
        'sales_team_member_id' => $rsmMember->id,
        'role_type' => RoleType::Rsm,
        'state_code' => 'WA',
    ]);
    TerritoryAssignment::factory()->create([
        'sales_team_member_id' => $seMember->id,
        'role_type' => RoleType::Se,
        'state_code' => 'OR',
    ]);

    Livewire::actingAs($this->admin)
        ->test(SalesTeamMemberManager::class)
        ->set('roleFilter', 'rsm')
        ->assertSee('RSM Person')
        ->assertDontSee('SE Person');
});

test('sales team manager can toggle member active status', function () {
    $member = SalesTeamMember::factory()->create(['is_active' => true]);

    Livewire::actingAs($this->admin)
        ->test(SalesTeamMemberManager::class)
        ->call('toggleActive', $member->id);

    expect($member->fresh()->is_active)->toBeFalse();
});

test('sales team manager can delete a member', function () {
    $member = SalesTeamMember::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(SalesTeamMemberManager::class)
        ->call('delete', $member->id);

    expect(SalesTeamMember::find($member->id))->toBeNull();
});

test('sales team form creates a new member', function () {
    Livewire::actingAs($this->admin)
        ->test(SalesTeamMemberForm::class)
        ->set('form.name', 'New Person')
        ->set('form.email', 'newperson@hirschsecure.com')
        ->set('form.phone', '555.123.4567')
        ->call('save')
        ->assertRedirect(route('admin.sales-team.index'));

    expect(SalesTeamMember::where('name', 'New Person')->exists())->toBeTrue();
});

test('sales team form updates an existing member', function () {
    $member = SalesTeamMember::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($this->admin)
        ->test(SalesTeamMemberForm::class, ['memberId' => $member->id])
        ->set('form.name', 'New Name')
        ->call('save')
        ->assertRedirect(route('admin.sales-team.index'));

    expect($member->fresh()->name)->toBe('New Name');
});

test('sales team form can add territory assignments', function () {
    Livewire::actingAs($this->admin)
        ->test(SalesTeamMemberForm::class)
        ->set('form.name', 'Territory Person')
        ->set('roleType', 'rsm')
        ->set('newStateCode', 'CA')
        ->set('newRegion', 'Northern CA')
        ->call('addAssignment')
        ->assertSet('assignments.0.state_code', 'CA')
        ->assertSet('assignments.0.region', 'Northern CA');
});

test('sales team form validates required fields', function () {
    Livewire::actingAs($this->admin)
        ->test(SalesTeamMemberForm::class)
        ->set('form.name', '')
        ->call('save')
        ->assertHasErrors(['form.name']);
});

test('sales team form saves territory assignments to database', function () {
    Livewire::actingAs($this->admin)
        ->test(SalesTeamMemberForm::class)
        ->set('form.name', 'Assignment Person')
        ->set('form.email', 'ap@hirschsecure.com')
        ->set('roleType', 'se')
        ->set('newStateCode', 'WA')
        ->call('addAssignment')
        ->call('save');

    $member = SalesTeamMember::where('name', 'Assignment Person')->first();
    expect($member)->not->toBeNull();
    expect($member->territoryAssignments)->toHaveCount(1);
    expect($member->territoryAssignments->first()->state_code)->toBe('WA');
    expect($member->territoryAssignments->first()->role_type)->toBe(RoleType::Se);
});
