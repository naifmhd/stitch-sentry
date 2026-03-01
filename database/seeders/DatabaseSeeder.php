<?php

namespace Database\Seeders;

use App\Models\DesignFile;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Naif Mohamed',
            'email' => 'naifmhd@gmail.com',
        ]);

        $organization = Organization::factory()->create([
            'name' => 'StitchSentry Demo Org',
        ]);

        $project = Project::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Demo Project',
        ]);

        DesignFile::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }
}
