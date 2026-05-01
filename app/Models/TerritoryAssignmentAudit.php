<?php

namespace App\Models;

use App\Enums\RoleType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'action',
    'role_type',
    'territory_code',
    'region',
    'sales_team_member_id',
    'member_name',
    'previous_member_id',
    'previous_member_name',
    'user_id',
    'user_name',
])]
class TerritoryAssignmentAudit extends Model
{
    protected function casts(): array
    {
        return [
            'role_type' => RoleType::class,
        ];
    }

    public function salesTeamMember(): BelongsTo
    {
        return $this->belongsTo(SalesTeamMember::class);
    }

    public function previousMember(): BelongsTo
    {
        return $this->belongsTo(SalesTeamMember::class, 'previous_member_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
