<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DesignFile>
 */
class DesignFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ext = fake()->randomElement(['dst', 'pes', 'jef', 'exp']);

        return [
            'organization_id' => \App\Models\Organization::factory(),
            'user_id' => \App\Models\User::factory(),
            'project_id' => null,
            'original_name' => fake()->word().'.'.$ext,
            'ext' => $ext,
            'size_bytes' => fake()->numberBetween(1024, 10_485_760),
            'checksum' => fake()->sha256(),
            'storage_path' => 'designs/'.fake()->uuid().'.'.$ext,
            'status' => 'uploaded',
        ];
    }
}
