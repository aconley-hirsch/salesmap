<?php

namespace App\Livewire\Admin;

use App\Enums\RoleType;
use App\Models\SalesTeamMember;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Manage Sales Team')]
class SalesTeamMemberManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function sortByColumn(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleActive(int $id): void
    {
        $member = SalesTeamMember::findOrFail($id);
        $member->update(['is_active' => ! $member->is_active]);

        $this->dispatch('flash', [
            'type' => 'success',
            'message' => 'Member status updated successfully.',
        ]);
    }

    public function delete(int $id): void
    {
        $member = SalesTeamMember::findOrFail($id);
        $member->delete();

        $this->dispatch('flash', [
            'type' => 'success',
            'message' => 'Member deleted successfully.',
        ]);
    }

    #[Computed]
    public function members()
    {
        return SalesTeamMember::query()
            ->with('territoryAssignments')
            ->withCount('territoryAssignments')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->roleFilter, function ($query) {
                $query->whereHas('territoryAssignments', function ($q) {
                    $q->where('role_type', $this->roleFilter);
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(21);
    }

    #[Computed]
    public function roleTypes(): array
    {
        return RoleType::cases();
    }

    public function render()
    {
        return view('livewire.admin.sales-team-member-manager');
    }
}
