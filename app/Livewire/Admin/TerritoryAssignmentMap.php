<?php

namespace App\Livewire\Admin;

use App\Enums\RoleType;
use App\Models\SalesTeamMember;
use App\Models\TerritoryAssignment;
use App\Models\TerritoryAssignmentAudit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class TerritoryAssignmentMap extends Component
{
    public string $activeRole = 'rsm';

    public ?int $armedMemberId = null;

    public ?string $modalState = null;

    public ?int $modalSelectedMemberId = null;

    public bool $splitMode = false;

    public string $mapDataJson = '';

    /** @var array<int, array{member_id: ?int, region: string}> */
    public array $splitRows = [];

    public array $newMember = [
        'name' => '',
        'email' => '',
        'phone' => '',
    ];

    protected function rules(): array
    {
        return [
            'newMember.name' => 'required|string|max:100',
            'newMember.email' => 'nullable|email|max:255',
            'newMember.phone' => 'nullable|string|max:50',
        ];
    }

    public function mount(): void
    {
        $this->splitRows = [
            ['member_id' => null, 'region' => ''],
            ['member_id' => null, 'region' => ''],
        ];
        $this->refreshMapData();
    }

    public function setRole(string $role): void
    {
        if (! in_array($role, array_column(RoleType::cases(), 'value'), true)) {
            return;
        }

        $this->activeRole = $role;
        $this->armedMemberId = null;
        $this->refreshMapData();
    }

    public function armMember(int $memberId): void
    {
        $this->armedMemberId = $this->armedMemberId === $memberId ? null : $memberId;
    }

    #[On('state-clicked')]
    public function openStatePopover(string $stateCode): void
    {
        $this->modalState = strtoupper($stateCode);
        $this->modalSelectedMemberId = $this->armedMemberId;
        $this->splitMode = false;
        $this->splitRows = [
            ['member_id' => $this->armedMemberId, 'region' => ''],
            ['member_id' => null, 'region' => ''],
        ];
        $this->modal('territory-state-modal')->show();
    }

    public function closeStatePopover(): void
    {
        $this->modalState = null;
        $this->modalSelectedMemberId = null;
        $this->splitMode = false;
        $this->modal('territory-state-modal')->close();
    }

    public function assignWholeState(): void
    {
        $memberId = $this->modalSelectedMemberId ?? $this->armedMemberId;

        if (! $this->modalState || ! $memberId) {
            return;
        }

        $member = SalesTeamMember::find($memberId);
        if (! $member) {
            return;
        }

        // Persist the picked member as the armed selection so subsequent clicks paint with them
        $this->armedMemberId = $member->id;

        $stateCode = $this->modalState;
        $color = $this->colorForMember($member->id, $this->activeRole);

        DB::transaction(function () use ($member, $stateCode, $color) {
            $existing = TerritoryAssignment::query()
                ->where('role_type', $this->activeRole)
                ->where('state_code', $stateCode)
                ->with('salesTeamMember')
                ->get();

            // If the only existing assignment is to this same member with no region, treat as no-op
            if ($existing->count() === 1
                && $existing->first()->sales_team_member_id === $member->id
                && $existing->first()->region === null) {
                return;
            }

            // Capture previous owners for audit
            $previous = $existing->filter(fn ($a) => $a->sales_team_member_id !== $member->id);

            // Remove all current assignments for this state+role
            TerritoryAssignment::query()
                ->where('role_type', $this->activeRole)
                ->where('state_code', $stateCode)
                ->delete();

            // Create the new whole-state assignment
            TerritoryAssignment::create([
                'sales_team_member_id' => $member->id,
                'role_type' => $this->activeRole,
                'state_code' => $stateCode,
                'region' => null,
                'color' => $color,
            ]);

            // Audit
            if ($previous->isEmpty()) {
                $this->logAudit('assigned', $stateCode, null, $member, null);
            } else {
                foreach ($previous as $prev) {
                    $this->logAudit('reassigned', $stateCode, null, $member, $prev->salesTeamMember);
                }
            }
        });

        $this->closeStatePopover();
        $this->refreshMapData();
        $this->dispatch('flash', [
            'type' => 'success',
            'message' => "Assigned {$stateCode} to {$member->name}.",
        ]);
    }

    public function unassignFromState(int $assignmentId): void
    {
        $assignment = TerritoryAssignment::with('salesTeamMember')->find($assignmentId);
        if (! $assignment || $assignment->role_type->value !== $this->activeRole) {
            return;
        }

        $stateCode = $assignment->state_code;
        $region = $assignment->region;
        $member = $assignment->salesTeamMember;

        $assignment->delete();

        $this->logAudit('unassigned', $stateCode, $region, null, $member);

        $this->refreshMapData();
        $this->dispatch('flash', [
            'type' => 'success',
            'message' => "Removed {$member->name} from {$stateCode}".($region ? " ({$region})." : '.'),
        ]);
    }

    public function enterSplitMode(): void
    {
        $this->splitMode = true;

        $existing = TerritoryAssignment::query()
            ->where('role_type', $this->activeRole)
            ->where('state_code', $this->modalState)
            ->get();

        if ($existing->count() > 0 && $existing->every(fn ($a) => $a->region !== null)) {
            $this->splitRows = $existing->map(fn ($a) => [
                'member_id' => $a->sales_team_member_id,
                'region' => $a->region,
            ])->values()->toArray();
        } else {
            $this->splitRows = [
                ['member_id' => $this->armedMemberId, 'region' => ''],
                ['member_id' => null, 'region' => ''],
            ];
        }
    }

    public function addSplitRow(): void
    {
        $this->splitRows[] = ['member_id' => null, 'region' => ''];
    }

    public function removeSplitRow(int $index): void
    {
        unset($this->splitRows[$index]);
        $this->splitRows = array_values($this->splitRows);
    }

    public function saveSplit(): void
    {
        if (! $this->modalState) {
            return;
        }

        $stateCode = $this->modalState;

        // Validate: every row needs a member and a region
        $rows = collect($this->splitRows)
            ->filter(fn ($r) => $r['member_id'] && trim($r['region']) !== '')
            ->values();

        if ($rows->count() < 2) {
            $this->addError('splitRows', 'A split needs at least two regions, each with a member and label.');

            return;
        }

        DB::transaction(function () use ($stateCode, $rows) {
            $existing = TerritoryAssignment::query()
                ->where('role_type', $this->activeRole)
                ->where('state_code', $stateCode)
                ->with('salesTeamMember')
                ->get();

            TerritoryAssignment::query()
                ->where('role_type', $this->activeRole)
                ->where('state_code', $stateCode)
                ->delete();

            foreach ($rows as $row) {
                $member = SalesTeamMember::find($row['member_id']);
                if (! $member) {
                    continue;
                }

                $color = $this->colorForMember($member->id, $this->activeRole);

                TerritoryAssignment::create([
                    'sales_team_member_id' => $member->id,
                    'role_type' => $this->activeRole,
                    'state_code' => $stateCode,
                    'region' => trim($row['region']),
                    'color' => $color,
                ]);

                $this->logAudit('assigned', $stateCode, trim($row['region']), $member, null);
            }

            // Audit removed previous assignments that aren't in the new set
            $newPairs = $rows->map(fn ($r) => $r['member_id'].'|'.trim($r['region']))->all();
            foreach ($existing as $prev) {
                $key = $prev->sales_team_member_id.'|'.($prev->region ?? '');
                if (! in_array($key, $newPairs, true)) {
                    $this->logAudit('unassigned', $stateCode, $prev->region, null, $prev->salesTeamMember);
                }
            }
        });

        $this->closeStatePopover();
        $this->refreshMapData();
        $this->dispatch('flash', [
            'type' => 'success',
            'message' => "Split assignments saved for {$stateCode}.",
        ]);
    }

    public function openCreateModal(): void
    {
        $this->newMember = ['name' => '', 'email' => '', 'phone' => ''];
        $this->resetValidation();
        $this->modal('territory-create-member')->show();
    }

    public function createMember(): void
    {
        $this->validate();

        $slug = Str::snake($this->newMember['name']);
        $original = $slug;
        $i = 1;
        while (SalesTeamMember::where('slug', $slug)->exists()) {
            $slug = $original.'_'.$i++;
        }

        $member = SalesTeamMember::create([
            'slug' => $slug,
            'name' => $this->newMember['name'],
            'email' => $this->newMember['email'] ?: null,
            'phone' => $this->newMember['phone'] ?: null,
            'is_active' => true,
            'role_type' => $this->activeRole,
        ]);

        $this->armedMemberId = $member->id;
        $this->newMember = ['name' => '', 'email' => '', 'phone' => ''];
        $this->modal('territory-create-member')->close();
        $this->refreshMapData();
        $this->dispatch('flash', [
            'type' => 'success',
            'message' => "Created {$member->name}. Click states to assign territories.",
        ]);
    }

    /**
     * All active members assigned to the current role, whether or not they have territory assignments.
     */
    #[Computed]
    public function members(): Collection
    {
        return SalesTeamMember::query()
            ->where('is_active', true)
            ->where('role_type', $this->activeRole)
            ->with(['territoryAssignments' => fn ($q) => $q->where('role_type', $this->activeRole)])
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{slug: string, name: string, color: string, state_count: int}[]
     */
    public function memberCards(): array
    {
        return $this->members->map(function (SalesTeamMember $m) {
            $first = $m->territoryAssignments->first();

            return [
                'id' => $m->id,
                'slug' => $m->slug,
                'name' => $m->name,
                'color' => $first?->color ?? $this->colorForMember($m->id, $this->activeRole),
                'state_count' => $m->territoryAssignments->count(),
            ];
        })->all();
    }

    /**
     * Map data shape consumed by D3 in resources/js/admin-territory-map.js
     */
    public function buildMapData(): array
    {
        $assignments = TerritoryAssignment::query()
            ->where('role_type', $this->activeRole)
            ->with('salesTeamMember')
            ->get();

        $people = [];
        $states = [];
        $colors = [];

        foreach ($assignments as $assignment) {
            $member = $assignment->salesTeamMember;
            if (! $member || ! $member->is_active) {
                continue;
            }

            $slug = $member->slug;

            $people[$slug] = [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email ?? '',
                'phone' => $member->phone ?? '',
            ];

            $colors[$slug] = $assignment->color;

            if ($assignment->region) {
                $states[$assignment->state_code][] = [
                    'id' => $assignment->id,
                    'key' => $slug,
                    'region' => $assignment->region,
                ];
            } else {
                $states[$assignment->state_code] = [
                    'id' => $assignment->id,
                    'key' => $slug,
                ];
            }
        }

        // Include armed-but-unassigned member so the legend stays in sync
        if ($this->armedMemberId) {
            $armed = SalesTeamMember::find($this->armedMemberId);
            if ($armed && $armed->is_active && ! isset($people[$armed->slug])) {
                $people[$armed->slug] = [
                    'id' => $armed->id,
                    'name' => $armed->name,
                    'email' => $armed->email ?? '',
                    'phone' => $armed->phone ?? '',
                ];
                $colors[$armed->slug] = $this->colorForMember($armed->id, $this->activeRole);
            }
        }

        return [
            'role' => $this->activeRole,
            'people' => $people,
            'states' => $states,
            'colors' => $colors,
            'armedSlug' => $this->armedMemberId
                ? optional(SalesTeamMember::find($this->armedMemberId))->slug
                : null,
        ];
    }

    public function modalAssignments(): array
    {
        if (! $this->modalState) {
            return [];
        }

        return TerritoryAssignment::query()
            ->where('role_type', $this->activeRole)
            ->where('state_code', $this->modalState)
            ->with('salesTeamMember')
            ->get()
            ->map(fn (TerritoryAssignment $a) => [
                'id' => $a->id,
                'member_id' => $a->sales_team_member_id,
                'name' => $a->salesTeamMember?->name ?? 'Unknown',
                'region' => $a->region,
                'color' => $a->color,
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.admin.territory-assignment-map', [
            'memberCards' => $this->memberCards(),
            'modalAssignments' => $this->modalAssignments(),
            'roleTypes' => RoleType::cases(),
            'allMembers' => SalesTeamMember::active()->inRole($this->activeRole)->orderBy('name')->get(),
        ])->title('Territory Map Editor');
    }

    private function refreshMapData(): void
    {
        $this->mapDataJson = json_encode($this->buildMapData());
    }

    private function colorForMember(int $memberId, string $role): string
    {
        $existing = TerritoryAssignment::query()
            ->where('sales_team_member_id', $memberId)
            ->where('role_type', $role)
            ->first();

        if ($existing) {
            return $existing->color;
        }

        $usedRaw = TerritoryAssignment::query()
            ->where('role_type', $role)
            ->where('sales_team_member_id', '!=', $memberId)
            ->pluck('color');

        $used = array_map(fn ($c) => strtolower($c), $usedRaw->all());

        foreach (SalesTeamMemberForm::PALETTE as $color) {
            if (! in_array(strtolower($color), $used, true)) {
                return $color;
            }
        }

        return SalesTeamMemberForm::PALETTE[0];
    }

    private function logAudit(
        string $action,
        string $stateCode,
        ?string $region,
        ?SalesTeamMember $member,
        ?SalesTeamMember $previous
    ): void {
        $user = auth()->user();

        TerritoryAssignmentAudit::create([
            'action' => $action,
            'role_type' => $this->activeRole,
            'state_code' => $stateCode,
            'region' => $region,
            'sales_team_member_id' => $member?->id,
            'member_name' => $member?->name,
            'previous_member_id' => $previous?->id,
            'previous_member_name' => $previous?->name,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
        ]);
    }
}
