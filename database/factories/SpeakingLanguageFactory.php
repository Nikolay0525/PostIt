<?php

namespace Database\Factories;

use App\Models\SpeakingLanguage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpeakingLanguage>
 */
class SpeakingLanguageFactory extends Factory
{
    public function definition(): array
    {
        // Three letters so a random code never collides with the seeded two-letter ones.
        return [
            'code' => fake()->unique()->regexify('[a-z]{3}'),
            'name' => fake()->unique()->word(),
        ];
    }
}
