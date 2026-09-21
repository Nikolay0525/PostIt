<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\SpeakingLanguage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->realText(100),
            'rules' => fake()->realText(100),
            'icon_url' => null,
            'is_private' => false,
            'group_language_id' => SpeakingLanguage::factory(),
        ];
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_private' => true,
        ]);
    }
}
