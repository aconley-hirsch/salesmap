<?php

namespace Database\Factories;

use App\Enums\RoleType;
use App\Models\SalesTeamMember;
use App\Models\TerritoryAssignment;
use App\Support\Territories;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TerritoryAssignment>
 */
class TerritoryAssignmentFactory extends Factory
{
    protected $model = TerritoryAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sales_team_member_id' => SalesTeamMember::factory(),
            'role_type' => fake()->randomElement(RoleType::cases()),
            'territory_code' => fake()->randomElement(array_keys(Territories::choices())),
            'region' => null,
            'split_direction' => null,
            'split_angle' => null,
            'split_order' => null,
            'split_percent' => null,
            'color' => fake()->hexColor(),
        ];
    }
}
