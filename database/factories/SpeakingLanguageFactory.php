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
        return [
            'name' => fake()->unique()->word(),
        ];
    }
}
