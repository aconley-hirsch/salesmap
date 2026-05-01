<?php

namespace App\Models;

use App\Enums\RoleType;
use Database\Factories\TerritoryAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sales_team_member_id', 'role_type', 'territory_code', 'region', 'split_direction', 'split_order', 'split_percent', 'color'])]
class TerritoryAssignment extends Model
{
    /** @use HasFactory<TerritoryAssignmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'role_type' => RoleType::class,
            'split_order' => 'integer',
            'split_percent' => 'integer',
        ];
    }

    public function salesTeamMember(): BelongsTo
    {
        return $this->belongsTo(SalesTeamMember::class);
    }
}
