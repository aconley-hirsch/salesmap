<?php

namespace Database\Factories;

use App\Enums\RoleType;
use App\Models\SalesTeamMember;
use App\Models\TerritoryAssignment;
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
        $stateCodes = [
            'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'DC', 'FL',
            'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME',
            'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH',
            'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI',
            'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY',
        ];

        return [
            'sales_team_member_id' => SalesTeamMember::factory(),
            'role_type' => fake()->randomElement(RoleType::cases()),
            'state_code' => fake()->randomElement($stateCodes),
            'region' => null,
            'color' => fake()->hexColor(),
        ];
    }
}
