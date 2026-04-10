<?php

namespace App\Models;

use App\Enums\RoleType;
use Database\Factories\SalesTeamMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'email', 'phone', 'is_active', 'role_type'])]
class SalesTeamMember extends Model
{
    /** @use HasFactory<SalesTeamMemberFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'role_type' => RoleType::class,
        ];
    }

    public function territoryAssignments(): HasMany
    {
        return $this->hasMany(TerritoryAssignment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInRole($query, string $role)
    {
        return $query->where('role_type', $role);
    }
}
