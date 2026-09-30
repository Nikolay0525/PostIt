<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupRuleVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupRuleVersion>
 */
class GroupRuleVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'rules' => array_map(fn () => [
                'text' => fake()->sentence(),
                'example' => fake()->boolean() ? fake()->sentence() : null,
            ], range(1, 3)),
        ];
    }
}
