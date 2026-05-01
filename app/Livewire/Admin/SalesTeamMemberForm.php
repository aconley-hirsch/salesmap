<?php

namespace App\Livewire\Admin;

use App\Enums\RoleType;
use App\Models\SalesTeamMember;
use App\Models\TerritoryAssignment;
use App\Support\Territories;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SalesTeamMemberForm extends Component
{
    /** @var string[] Curated palette of visually distinct colors */
    public const PALETTE = [
        '#09a49d', '#362e83', '#f39209', '#a9d18e', '#8fceed',
        '#be2122', '#222a35', '#37a9e1', '#aa871b', '#036778',
        '#944cd7', '#88d948', '#e848ab', '#5f3260', '#7f7f7f',
        '#66501d', '#5abf3a', '#6a3a8a', '#5ab8e0', '#e8c840',
        '#2a4a8a', '#d89030', '#e8a030', '#ffed8a', '#c89030', '#4a2a6a',
        '#e74c3c', '#1abc9c', '#3498db', '#e67e22', '#1a5276', '#c0392b', '#27ae60', '#8e44ad',
    ];

    public ?SalesTeamMember $member = null;

    public array $form = [];

    public string $roleType = 'rsm';

    public string $color = '#09a49d';

    /** @var array<int, array{id: int|null, territory_code: string, region: string|null}> */
    public array $assignments = [];

    public string $newTerritoryCode = '';

    public string $newRegion = '';

    protected function rules(): array
    {
        return [
            'form.name' => 'required|string|max:100',
            'form.email' => 'nullable|email|max:255',
            'form.phone' => 'nullable|string|max:50',
            'form.is_active' => 'boolean',
            'roleType' => 'required|string',
            'color' => 'required|string|max:7',
        ];
    }

    public function mount(?int $memberId = null): void
    {
        if ($memberId) {
            $this->member = SalesTeamMember::findOrFail($memberId);
            $this->form = [
                'name' => $this->member->name,
                'email' => $this->member->email,
                'phone' => $this->member->phone,
                'is_active' => $this->member->is_active,
            ];

            if ($this->member->role_type) {
                $this->roleType = $this->member->role_type->value;
            }

            $firstAssignment = $this->member->territoryAssignments->first();
            if ($firstAssignment) {
                $this->roleType = $firstAssignment->role_type->value;
                $this->color = $firstAssignment->color;
            }

            $this->assignments = $this->member->territoryAssignments
                ->map(fn (TerritoryAssignment $a) => [
                    'id' => $a->id,
                    'territory_code' => $a->territory_code,
                    'region' => $a->region,
                ])
                ->toArray();
        } else {
            $this->form = [
                'name' => '',
                'email' => '',
                'phone' => '',
                'is_active' => true,
            ];
            $this->autoAssignColor();
        }
    }

    public function selectColor(string $color): void
    {
        $this->color = $color;
    }

    public function updatedRoleType(): void
    {
        $this->autoAssignColor();
    }

    public function addAssignment(): void
    {
        $this->validate([
            'newTerritoryCode' => 'required|string',
        ]);

        $territoryCode = Territories::normalize($this->newTerritoryCode);

        if (! Territories::isValid($territoryCode)) {
            $this->addError('newTerritoryCode', 'Select a valid territory.');

            return;
        }

        $this->assignments[] = [
            'id' => null,
            'territory_code' => $territoryCode,
            'region' => $this->newRegion ?: null,
        ];

        $this->newTerritoryCode = '';
        $this->newRegion = '';
    }

    public function removeAssignment(int $index): void
    {
        unset($this->assignments[$index]);
        $this->assignments = array_values($this->assignments);
    }

    /**
     * Colors used by OTHER members in the current role type, mapped to member name.
     *
     * @return array<string, string> color => member name
     */
    #[Computed]
    public function usedColors(): array
    {
        $query = TerritoryAssignment::query()
            ->where('role_type', $this->roleType)
            ->with('salesTeamMember');

        if ($this->member) {
            $query->where('sales_team_member_id', '!=', $this->member->id);
        }

        $map = [];
        foreach ($query->get() as $assignment) {
            $map[strtolower($assignment->color)] = $assignment->salesTeamMember->name;
        }

        return $map;
    }

    #[Computed]
    public function roleTypes(): array
    {
        return RoleType::cases();
    }

    #[Computed]
    public function territoryChoices(): array
    {
        return Territories::choices();
    }

    public function delete(): mixed
    {
        if ($this->member) {
            $this->member->delete();

            $this->dispatch('flash', [
                'type' => 'success',
                'message' => 'Member deleted successfully.',
            ]);
        }

        return $this->redirect(route('admin.sales-team.index'), navigate: true);
    }

    public function save(): mixed
    {
        $this->validate();

        $slug = $this->member?->slug ?? Str::snake($this->form['name']);

        if (! $this->member) {
            $originalSlug = $slug;
            $counter = 1;
            while (SalesTeamMember::where('slug', $slug)->exists()) {
                $slug = $originalSlug.'_'.$counter;
                $counter++;
            }
        }

        $payload = array_merge($this->form, ['role_type' => $this->roleType]);

        if ($this->member) {
            $this->member->update(array_merge($payload, ['slug' => $this->member->slug]));
        } else {
            $this->member = SalesTeamMember::create(array_merge($payload, ['slug' => $slug]));
        }

        // Sync territory assignments — all share the same role and color
        $existingIds = collect($this->assignments)->pluck('id')->filter()->toArray();
        $this->member->territoryAssignments()->whereNotIn('id', $existingIds)->delete();

        foreach ($this->assignments as $assignment) {
            if ($assignment['id']) {
                TerritoryAssignment::where('id', $assignment['id'])->update([
                    'role_type' => $this->roleType,
                    'territory_code' => Territories::normalize($assignment['territory_code']),
                    'region' => $assignment['region'],
                    'color' => $this->color,
                ]);
            } else {
                $this->member->territoryAssignments()->create([
                    'role_type' => $this->roleType,
                    'territory_code' => Territories::normalize($assignment['territory_code']),
                    'region' => $assignment['region'],
                    'color' => $this->color,
                ]);
            }
        }

        $this->dispatch('flash', [
            'type' => 'success',
            'message' => $this->member->wasRecentlyCreated ? 'Member created successfully.' : 'Member updated successfully.',
        ]);

        return $this->redirect(route('admin.sales-team.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.sales-team-member-form')
            ->title($this->member ? 'Edit Sales Team Member' : 'Add Sales Team Member');
    }

    private function autoAssignColor(): void
    {
        $used = array_keys($this->usedColors);
        $available = array_filter(self::PALETTE, fn ($c) => ! in_array(strtolower($c), $used));
        $this->color = reset($available) ?: self::PALETTE[0];
    }
}
