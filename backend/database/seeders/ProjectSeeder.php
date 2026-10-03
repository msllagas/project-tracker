<?php

namespace Database\Seeders;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProjectSeeder extends Seeder
{
    /**
     * Seed the projects provided in database/data/test_data.json.
     *
     * The file uses camelCase keys and display labels ("In Progress"), which are
     * mapped to the column names and enum values used by the application. IDs
     * are left to the database so its sequence stays in sync.
     */
    public function run(): void
    {
        $projects = File::json(database_path('data/test_data.json'));

        foreach ($projects as $project) {
            Project::create([
                'client_name' => $project['clientName'],
                'name' => $project['projectName'],
                'description' => $project['description'],
                'status' => ProjectStatus::from(Str::slug($project['status'], '_')),
                'priority' => ProjectPriority::from(Str::slug($project['priority'], '_')),
                'start_date' => $project['startDate'],
                'due_date' => $project['dueDate'],
            ]);
        }
    }
}
