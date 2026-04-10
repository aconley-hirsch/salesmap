<?php

use App\Enums\RoleType;
use App\Livewire\Admin\TerritoryAssignmentMap;
use App\Models\SalesTeamMember;
use App\Models\TerritoryAssignment;
use App\Models\TerritoryAssignmentAudit;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

test('territory map editor requires admin auth', function () {
    $this->get(route('admin.territory-map.edit'))
        ->assertRedirect();
});

test('territory map editor renders for admin', function () {
    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->assertSuccessful()
        ->assertSee('Territory Map Editor');
});

test('arming a member sets the armed id', function () {
    $member = SalesTeamMember::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->call('armMember', $member->id)
        ->assertSet('armedMemberId', $member->id)
        ->call('armMember', $member->id)
        ->assertSet('armedMemberId', null);
});

test('changing role tab clears armed member', function () {
    $member = SalesTeamMember::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->call('armMember', $member->id)
        ->call('setRole', 'se')
        ->assertSet('armedMemberId', null)
        ->assertSet('activeRole', 'se');
});

test('clicking a state opens the modal with that state', function () {
    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->dispatch('state-clicked', stateCode: 'CA')
        ->assertSet('modalState', 'CA');
});

test('assigning a whole state creates the assignment and an audit log', function () {
    $member = SalesTeamMember::factory()->create(['name' => 'Jane Doe']);

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->call('armMember', $member->id)
        ->dispatch('state-clicked', stateCode: 'CA')
        ->call('assignWholeState');

    $assignment = TerritoryAssignment::where('state_code', 'CA')->first();
    expect($assignment)->not->toBeNull();
    expect($assignment->sales_team_member_id)->toBe($member->id);
    expect($assignment->role_type)->toBe(RoleType::Rsm);
    expect($assignment->region)->toBeNull();

    $audit = TerritoryAssignmentAudit::where('state_code', 'CA')->first();
    expect($audit)->not->toBeNull();
    expect($audit->action)->toBe('assigned');
    expect($audit->member_name)->toBe('Jane Doe');
    expect($audit->user_id)->toBe($this->admin->id);
});

test('assigning a state already owned by another member reassigns and logs the previous owner', function () {
    $previous = SalesTeamMember::factory()->create(['name' => 'Old Owner']);
    $next = SalesTeamMember::factory()->create(['name' => 'New Owner']);

    TerritoryAssignment::factory()->create([
        'sales_team_member_id' => $previous->id,
        'role_type' => RoleType::Rsm,
        'state_code' => 'TX',
        'color' => '#aaaaaa',
    ]);

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->call('armMember', $next->id)
        ->dispatch('state-clicked', stateCode: 'TX')
        ->call('assignWholeState');

    expect(TerritoryAssignment::where('state_code', 'TX')->where('role_type', 'rsm')->count())->toBe(1);
    expect(TerritoryAssignment::where('state_code', 'TX')->first()->sales_team_member_id)->toBe($next->id);

    $audit = TerritoryAssignmentAudit::where('state_code', 'TX')->where('action', 'reassigned')->first();
    expect($audit)->not->toBeNull();
    expect($audit->member_name)->toBe('New Owner');
    expect($audit->previous_member_name)->toBe('Old Owner');
});

test('unassigning a state removes the assignment and logs the action', function () {
    $member = SalesTeamMember::factory()->create(['name' => 'Sam']);

    $assignment = TerritoryAssignment::factory()->create([
        'sales_team_member_id' => $member->id,
        'role_type' => RoleType::Rsm,
        'state_code' => 'OR',
        'color' => '#bbbbbb',
    ]);

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->call('unassignFromState', $assignment->id);

    expect(TerritoryAssignment::find($assignment->id))->toBeNull();

    $audit = TerritoryAssignmentAudit::where('state_code', 'OR')->where('action', 'unassigned')->first();
    expect($audit)->not->toBeNull();
    expect($audit->previous_member_name)->toBe('Sam');
});

test('saving a split creates two regional assignments', function () {
    $north = SalesTeamMember::factory()->create(['name' => 'Northy']);
    $south = SalesTeamMember::factory()->create(['name' => 'Southy']);

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->dispatch('state-clicked', stateCode: 'CA')
        ->set('splitRows', [
            ['member_id' => $north->id, 'region' => 'Northern CA'],
            ['member_id' => $south->id, 'region' => 'Southern CA'],
        ])
        ->call('saveSplit');

    $assignments = TerritoryAssignment::where('state_code', 'CA')->where('role_type', 'rsm')->get();
    expect($assignments)->toHaveCount(2);
    expect($assignments->pluck('region')->all())->toContain('Northern CA', 'Southern CA');
});

test('split with fewer than two valid rows shows an error', function () {
    $member = SalesTeamMember::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->dispatch('state-clicked', stateCode: 'NV')
        ->set('splitRows', [
            ['member_id' => $member->id, 'region' => 'Northern NV'],
            ['member_id' => null, 'region' => ''],
        ])
        ->call('saveSplit')
        ->assertHasErrors('splitRows');

    expect(TerritoryAssignment::where('state_code', 'NV')->count())->toBe(0);
});

test('quick create member creates the member and arms them', function () {
    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->set('newMember.name', 'Brand New')
        ->set('newMember.email', 'bnew@hirschsecure.com')
        ->call('createMember');

    $member = SalesTeamMember::where('name', 'Brand New')->first();
    expect($member)->not->toBeNull();
    expect($member->email)->toBe('bnew@hirschsecure.com');
});

test('quick create member sets role_type to the active role', function () {
    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->call('setRole', 'se')
        ->set('newMember.name', 'New SE')
        ->call('createMember');

    $member = SalesTeamMember::where('name', 'New SE')->first();
    expect($member->role_type->value)->toBe('se');
});

test('quick create member validates required name', function () {
    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->set('newMember.name', '')
        ->call('createMember')
        ->assertHasErrors('newMember.name');
});

test('color is reused for the same member across states in the same role', function () {
    $member = SalesTeamMember::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->call('armMember', $member->id)
        ->dispatch('state-clicked', stateCode: 'WA')
        ->call('assignWholeState')
        ->dispatch('state-clicked', stateCode: 'OR')
        ->call('assignWholeState');

    $colors = TerritoryAssignment::where('sales_team_member_id', $member->id)
        ->where('role_type', 'rsm')
        ->pluck('color')
        ->unique();

    expect($colors)->toHaveCount(1);
});

test('members list shows all active members in the active role regardless of assignments', function () {
    $rsmAssigned = SalesTeamMember::factory()->create(['name' => 'RSM Assigned', 'role_type' => 'rsm']);
    $rsmUnassigned = SalesTeamMember::factory()->create(['name' => 'RSM Unassigned', 'role_type' => 'rsm']);
    $seMember = SalesTeamMember::factory()->create(['name' => 'SE Person', 'role_type' => 'se']);

    TerritoryAssignment::factory()->create([
        'sales_team_member_id' => $rsmAssigned->id,
        'role_type' => RoleType::Rsm,
        'state_code' => 'WA',
    ]);

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->assertSee('RSM Assigned')
        ->assertSee('RSM Unassigned')
        ->assertDontSee('SE Person')
        ->call('setRole', 'se')
        ->assertSee('SE Person')
        ->assertDontSee('RSM Assigned')
        ->assertDontSee('RSM Unassigned');
});
