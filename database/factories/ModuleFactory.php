<?php

namespace Database\Factories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $planId = 1;
        $planId = $planId > 50 ? 1 : $planId;
        return [
            'name' => $this->faker->sentence(3),
            'plan_id' => $planId++, // 1-50 sequence
        ];
    }
}
