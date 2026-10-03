<?php

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Database\Seeders\ProjectSeeder;

it('seeds every project from the test data file', function () {
    $this->seed(ProjectSeeder::class);

    $this->assertDatabaseCount('projects', 12);
});

it('maps the test data fields and labels to project attributes', function () {
    $this->seed(ProjectSeeder::class);

    $project = Project::firstWhere('client_name', 'Bright Realty');
    expect($project)
        ->name->toBe('Property Listing Portal')
        ->description->toBe('Build a portal for managing property listings.')
        ->status->toBe(ProjectStatus::OnHold)
        ->priority->toBe(ProjectPriority::Medium)
        ->start_date->toDateString()->toBe('2026-05-15')
        ->due_date->toDateString()->toBe('2026-07-30');
});
