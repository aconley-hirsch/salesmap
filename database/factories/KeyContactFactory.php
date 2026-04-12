<?php

namespace Database\Factories;

use App\Models\KeyContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KeyContact>
 */
class KeyContactFactory extends Factory
{
    protected $model = KeyContact::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'title' => fake()->jobTitle(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'notes' => null,
            'group_name' => 'Leaders',
            'group_order' => 0,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
