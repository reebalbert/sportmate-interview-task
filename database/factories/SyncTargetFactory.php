<?php

namespace Database\Factories;

use App\Enums\SyncTargetType;
use App\Models\SyncTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyncTarget>
 */
class SyncTargetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->userName(),
            'type' => SyncTargetType::User,
        ];
    }
}