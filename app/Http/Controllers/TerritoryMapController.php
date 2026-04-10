<?php

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Models\TerritoryAssignment;
use Illuminate\View\View;

class TerritoryMapController extends Controller
{
    public function index(): View
    {
        $assignments = TerritoryAssignment::query()
            ->with('salesTeamMember')
            ->whereHas('salesTeamMember', fn ($q) => $q->where('is_active', true))
            ->orderBy('state_code')
            ->orderBy('region')
            ->orderBy('id')
            ->get();

        $people = [];
        $maps = [];
        $colors = [];

        foreach ($assignments as $assignment) {
            $member = $assignment->salesTeamMember;
            $slug = $member->slug;
            $roleType = $assignment->role_type->value;
            $stateCode = $assignment->state_code;

            $people[$slug] ??= [
                'name' => $member->name,
                'email' => $member->email ?? '',
                'phone' => $member->phone ?? '',
            ];

            $colors[$roleType][$slug] = $assignment->color;

            if ($assignment->region) {
                $maps[$roleType][$stateCode][] = [
                    'key' => $slug,
                    'region' => $assignment->region,
                ];
            } else {
                $maps[$roleType][$stateCode] = $slug;
            }
        }

        $roles = array_map(
            fn (RoleType $role) => ['key' => $role->value, 'label' => $role->label()],
            RoleType::cases()
        );

        return view('territory-map', [
            'mapData' => [
                'people' => $people,
                'maps' => $maps,
                'colors' => $colors,
                'roles' => $roles,
            ],
        ]);
    }
}
