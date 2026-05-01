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

test('clicking a territory opens the modal with that territory', function () {
    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->dispatch('territory-clicked', territoryCode: 'CA-ON')
        ->assertSet('modalTerritory', 'CA-ON');
});

test('legacy two letter state click opens the matching US territory', function () {
    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->dispatch('state-clicked', stateCode: 'CA')
        ->assertSet('modalTerritory', 'US-CA');
});

test('territory modals use the branded admin background', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('[data-modal="territory-state-modal"]')
        ->toContain('[data-modal="territory-create-member"]')
        ->toContain('background: #0a2a3d !important;');
});

test('assigning a whole territory creates the assignment and an audit log', function () {
    $member = SalesTeamMember::factory()->create(['name' => 'Jane Doe']);

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->call('armMember', $member->id)
        ->dispatch('territory-clicked', territoryCode: 'US-CA')
        ->call('assignWholeTerritory');

    $assignment = TerritoryAssignment::where('territory_code', 'US-CA')->first();
    expect($assignment)->not->toBeNull();
    expect($assignment->sales_team_member_id)->toBe($member->id);
    expect($assignment->role_type)->toBe(RoleType::Rsm);
    expect($assignment->region)->toBeNull();

    $audit = TerritoryAssignmentAudit::where('territory_code', 'US-CA')->first();
    expect($audit)->not->toBeNull();
    expect($audit->action)->toBe('assigned');
    expect($audit->member_name)->toBe('Jane Doe');
    expect($audit->user_id)->toBe($this->admin->id);
});

test('assigning a territory already owned by another member reassigns and logs the previous owner', function () {
    $previous = SalesTeamMember::factory()->create(['name' => 'Old Owner']);
    $next = SalesTeamMember::factory()->create(['name' => 'New Owner']);

    TerritoryAssignment::factory()->create([
        'sales_team_member_id' => $previous->id,
        'role_type' => RoleType::Rsm,
        'territory_code' => 'US-TX',
        'color' => '#aaaaaa',
    ]);

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->call('armMember', $next->id)
        ->dispatch('territory-clicked', territoryCode: 'US-TX')
        ->call('assignWholeTerritory');

    expect(TerritoryAssignment::where('territory_code', 'US-TX')->where('role_type', 'rsm')->count())->toBe(1);
    expect(TerritoryAssignment::where('territory_code', 'US-TX')->first()->sales_team_member_id)->toBe($next->id);

    $audit = TerritoryAssignmentAudit::where('territory_code', 'US-TX')->where('action', 'reassigned')->first();
    expect($audit)->not->toBeNull();
    expect($audit->member_name)->toBe('New Owner');
    expect($audit->previous_member_name)->toBe('Old Owner');
});

test('unassigning a territory removes the assignment and logs the action', function () {
    $member = SalesTeamMember::factory()->create(['name' => 'Sam']);

    $assignment = TerritoryAssignment::factory()->create([
        'sales_team_member_id' => $member->id,
        'role_type' => RoleType::Rsm,
        'territory_code' => 'US-OR',
        'color' => '#bbbbbb',
    ]);

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->call('unassignFromTerritory', $assignment->id);

    expect(TerritoryAssignment::find($assignment->id))->toBeNull();

    $audit = TerritoryAssignmentAudit::where('territory_code', 'US-OR')->where('action', 'unassigned')->first();
    expect($audit)->not->toBeNull();
    expect($audit->previous_member_name)->toBe('Sam');
});

test('saving a split creates two regional assignments', function () {
    $north = SalesTeamMember::factory()->create(['name' => 'Northy']);
    $south = SalesTeamMember::factory()->create(['name' => 'Southy']);

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->dispatch('territory-clicked', territoryCode: 'US-CA')
        ->set('splitRows', [
            ['member_id' => $north->id, 'region' => 'Northern CA'],
            ['member_id' => $south->id, 'region' => 'Southern CA'],
        ])
        ->call('saveSplit');

    $assignments = TerritoryAssignment::where('territory_code', 'US-CA')->where('role_type', 'rsm')->get();
    expect($assignments)->toHaveCount(2);
    expect($assignments->pluck('region')->all())->toContain('Northern CA', 'Southern CA');
});

test('saving a split stores dynamic direction order and percentages', function () {
    $west = SalesTeamMember::factory()->create(['name' => 'West']);
    $middle = SalesTeamMember::factory()->create(['name' => 'Middle']);
    $east = SalesTeamMember::factory()->create(['name' => 'East']);

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->dispatch('territory-clicked', territoryCode: 'US-TN')
        ->set('splitDirection', 'west_east')
        ->set('splitRows', [
            ['member_id' => $west->id, 'region' => 'West TN', 'percent' => 30],
            ['member_id' => $middle->id, 'region' => 'Middle TN', 'percent' => 40],
            ['member_id' => $east->id, 'region' => 'East TN', 'percent' => 30],
        ])
        ->call('saveSplit');

    $assignments = TerritoryAssignment::where('territory_code', 'US-TN')
        ->where('role_type', 'rsm')
        ->orderBy('split_order')
        ->get();

    expect($assignments)->toHaveCount(3);
    expect($assignments->pluck('region')->all())->toBe(['West TN', 'Middle TN', 'East TN']);
    expect($assignments->pluck('split_direction')->unique()->values()->all())->toBe(['west_east']);
    expect($assignments->pluck('split_percent')->all())->toBe([30, 40, 30]);
});

test('split percentages must total one hundred when provided', function () {
    $first = SalesTeamMember::factory()->create();
    $second = SalesTeamMember::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->dispatch('territory-clicked', territoryCode: 'US-TX')
        ->set('splitRows', [
            ['member_id' => $first->id, 'region' => 'North TX', 'percent' => 60],
            ['member_id' => $second->id, 'region' => 'South TX', 'percent' => 60],
        ])
        ->call('saveSplit')
        ->assertHasErrors('splitRows');
});

test('can assign and split Canadian and global territories', function () {
    $canada = SalesTeamMember::factory()->create(['name' => 'Canada Owner']);
    $emeaNorth = SalesTeamMember::factory()->create(['name' => 'EMEA North']);
    $emeaSouth = SalesTeamMember::factory()->create(['name' => 'EMEA South']);

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->call('armMember', $canada->id)
        ->dispatch('territory-clicked', territoryCode: 'CA-ON')
        ->call('assignWholeTerritory')
        ->dispatch('territory-clicked', territoryCode: 'REG-EMEA')
        ->set('splitRows', [
            ['member_id' => $emeaNorth->id, 'region' => 'Europe'],
            ['member_id' => $emeaSouth->id, 'region' => 'Middle East and Africa'],
        ])
        ->call('saveSplit');

    expect(TerritoryAssignment::where('territory_code', 'CA-ON')->where('sales_team_member_id', $canada->id)->exists())->toBeTrue();

    $globalAssignments = TerritoryAssignment::where('territory_code', 'REG-EMEA')
        ->where('role_type', 'rsm')
        ->pluck('region')
        ->all();

    expect($globalAssignments)->toContain('Europe', 'Middle East and Africa');
});

test('public map data includes US Canada and global territory codes', function () {
    $member = SalesTeamMember::factory()->create(['slug' => 'territory-owner', 'name' => 'Territory Owner']);

    foreach (['US-CA', 'CA-ON', 'REG-EMEA', 'REG-APAC'] as $territoryCode) {
        TerritoryAssignment::factory()->create([
            'sales_team_member_id' => $member->id,
            'role_type' => RoleType::Rsm,
            'territory_code' => $territoryCode,
        ]);
    }

    $this->get(route('territory-map'))
        ->assertSuccessful()
        ->assertSee('"territories"', false)
        ->assertSee('US-CA')
        ->assertSee('CA-ON')
        ->assertSee('REG-EMEA')
        ->assertSee('REG-APAC');
});

test('public map handles mixed whole and split rows for the same territory', function () {
    $wholeOwner = SalesTeamMember::factory()->create(['slug' => 'whole-owner']);
    $splitOwner = SalesTeamMember::factory()->create(['slug' => 'split-owner']);

    TerritoryAssignment::factory()->create([
        'sales_team_member_id' => $wholeOwner->id,
        'role_type' => RoleType::Rsm,
        'territory_code' => 'US-CA',
        'region' => null,
    ]);

    TerritoryAssignment::factory()->create([
        'sales_team_member_id' => $splitOwner->id,
        'role_type' => RoleType::Rsm,
        'territory_code' => 'US-CA',
        'region' => 'Northern CA',
    ]);

    $this->get(route('territory-map'))
        ->assertSuccessful()
        ->assertSee('whole-owner')
        ->assertSee('split-owner')
        ->assertSee('Northern CA');
});

test('split with fewer than two valid rows shows an error', function () {
    $member = SalesTeamMember::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->dispatch('territory-clicked', territoryCode: 'US-NV')
        ->set('splitRows', [
            ['member_id' => $member->id, 'region' => 'Northern NV'],
            ['member_id' => null, 'region' => ''],
        ])
        ->call('saveSplit')
        ->assertHasErrors('splitRows');

    expect(TerritoryAssignment::where('territory_code', 'US-NV')->count())->toBe(0);
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

test('color is reused for the same member across territories in the same role', function () {
    $member = SalesTeamMember::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(TerritoryAssignmentMap::class)
        ->call('armMember', $member->id)
        ->dispatch('territory-clicked', territoryCode: 'US-WA')
        ->call('assignWholeTerritory')
        ->dispatch('territory-clicked', territoryCode: 'CA-ON')
        ->call('assignWholeTerritory');

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
        'territory_code' => 'US-WA',
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
