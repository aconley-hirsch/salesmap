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
            ->orderBy('territory_code')
            ->orderBy('split_order')
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
            $territoryCode = $assignment->territory_code;

            $people[$slug] ??= [
                'name' => $member->name,
                'email' => $member->email ?? '',
                'phone' => $member->phone ?? '',
            ];

            $colors[$roleType][$slug] = $assignment->color;

            if ($assignment->region) {
                if (isset($maps[$roleType][$territoryCode]) && ! is_array($maps[$roleType][$territoryCode])) {
                    $maps[$roleType][$territoryCode] = [
                        ['key' => $maps[$roleType][$territoryCode], 'region' => null],
                    ];
                }

                $maps[$roleType][$territoryCode][] = [
                    'key' => $slug,
                    'region' => $assignment->region,
                    'splitDirection' => $assignment->split_direction ?: 'west_east',
                    'splitOrder' => $assignment->split_order,
                    'splitPercent' => $assignment->split_percent,
                ];
            } elseif (! isset($maps[$roleType][$territoryCode])) {
                $maps[$roleType][$territoryCode] = $slug;
            }
        }

        $roles = array_map(
            fn (RoleType $role) => ['key' => $role->value, 'label' => $role->label()],
            RoleType::cases()
        );

        return view('territory-map', [
            'mapData' => [
                'people' => $people,
                'territories' => $maps,
                'colors' => $colors,
                'roles' => $roles,
            ],
        ]);
    }
}
