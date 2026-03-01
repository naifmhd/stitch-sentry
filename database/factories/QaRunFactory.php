<?php

namespace Database\Factories;

use App\Models\DesignFile;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QaRun>
 */
class QaRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'design_file_id' => DesignFile::factory(),
            'preset' => 'custom',
            'status' => 'queued',
            'stage' => null,
            'progress' => 0,
            'score' => null,
            'risk_level' => null,
            'error_code' => null,
            'support_id' => null,
            'started_at' => null,
            'finished_at' => null,
        ];
    }

    public function running(): static
    {
        return $this->state([
            'status' => 'running',
            'stage' => 'ingest',
            'progress' => 5,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'stage' => 'export',
            'progress' => 100,
            'score' => fake()->numberBetween(50, 100),
            'risk_level' => fake()->randomElement(['low', 'medium', 'high']),
            'started_at' => now()->subMinutes(2),
            'finished_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => 'failed',
            'error_code' => 'PARSER_TIMEOUT',
            'support_id' => 'SS-'.strtoupper(fake()->bothify('######')),
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);
    }
}
