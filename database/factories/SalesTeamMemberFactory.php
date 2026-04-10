<?php

namespace Database\Factories;

use App\Models\SalesTeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SalesTeamMember>
 */
class SalesTeamMemberFactory extends Factory
{
    protected $model = SalesTeamMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'slug' => Str::snake($firstName.'_'.$lastName).'_'.fake()->unique()->numberBetween(1, 9999),
            'name' => $firstName.' '.$lastName,
            'email' => Str::lower($firstName[0].$lastName).'@hirschsecure.com',
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
            'role_type' => 'rsm',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
