<?php

use App\Models\Project;
use App\Models\User;

/**
 * @return array<string, string|null>
 */
function validProjectPayload(array $overrides = []): array
{
    return array_merge([
        'client_name' => 'Acme Corp',
        'name' => 'Website Redesign',
        'description' => 'Refresh the marketing site.',
        'status' => 'in_progress',
        'priority' => 'high',
        'start_date' => '2026-10-01',
        'due_date' => '2026-12-15',
    ], $overrides);
}

it('returns 401 for unauthenticated requests', function (string $method, string $uri) {
    $this->json($method, $uri)->assertUnauthorized();
})->with([
    'list' => ['GET', '/api/projects'],
    'show' => ['GET', '/api/projects/1'],
    'create' => ['POST', '/api/projects'],
    'update' => ['PUT', '/api/projects/1'],
    'delete' => ['DELETE', '/api/projects/1'],
]);

describe('index', function () {
    it('lists all projects', function () {
        $projects = Project::factory()->count(3)->create();

        $response = $this->actingAs(User::factory()->create())->getJson('/api/projects');

        $response->assertOk()->assertJsonCount(3, 'data');
        expect($response->json('data.*.id'))->toEqualCanonicalizing($projects->pluck('id')->all());
    });

    it('lists the newest projects first by default', function () {
        $older = Project::factory()->create(['created_at' => now()->subDay()]);
        $newer = Project::factory()->create(['created_at' => now()]);

        $response = $this->actingAs(User::factory()->create())->getJson('/api/projects');

        expect($response->json('data.*.id'))->toBe([$newer->id, $older->id]);
    });

    it('searches client and project names case-insensitively', function () {
        $byClient = Project::factory()->create(['client_name' => 'Globex Corporation', 'name' => 'SEO Audit']);
        $byName = Project::factory()->create(['client_name' => 'Initech', 'name' => 'Globex Rebrand']);
        Project::factory()->create(['client_name' => 'Initrode', 'name' => 'Brand Refresh']);

        $response = $this->actingAs(User::factory()->create())->getJson('/api/projects?search=gLoBeX');

        expect($response->json('data.*.id'))->toEqualCanonicalizing([$byClient->id, $byName->id]);
    });

    it('filters by status and priority', function () {
        $match = Project::factory()->create(['status' => 'on_hold', 'priority' => 'high']);
        Project::factory()->create(['status' => 'on_hold', 'priority' => 'low']);
        Project::factory()->create(['status' => 'planning', 'priority' => 'high']);

        $response = $this->actingAs(User::factory()->create())->getJson('/api/projects?status=on_hold&priority=high');

        expect($response->json('data.*.id'))->toBe([$match->id]);
    });

    it('sorts by priority from most to least important', function () {
        $medium = Project::factory()->create(['priority' => 'medium']);
        $high = Project::factory()->create(['priority' => 'high']);
        $low = Project::factory()->create(['priority' => 'low']);

        $response = $this->actingAs(User::factory()->create())->getJson('/api/projects?sort=-priority');

        expect($response->json('data.*.id'))->toBe([$high->id, $medium->id, $low->id]);
    });

    it('sorts by status in workflow order', function () {
        $completed = Project::factory()->create(['status' => 'completed']);
        $planning = Project::factory()->create(['status' => 'planning']);
        $onHold = Project::factory()->create(['status' => 'on_hold']);
        $inProgress = Project::factory()->create(['status' => 'in_progress']);

        $response = $this->actingAs(User::factory()->create())->getJson('/api/projects?sort=status');

        expect($response->json('data.*.id'))->toBe([$planning->id, $inProgress->id, $onHold->id, $completed->id]);
    });

    it('lists projects without a due date last in either direction', function (string $sort, array $expectedOrder) {
        $projects = [
            'none' => Project::factory()->create(['start_date' => null, 'due_date' => null]),
            'early' => Project::factory()->create(['start_date' => null, 'due_date' => '2026-11-01']),
            'late' => Project::factory()->create(['start_date' => null, 'due_date' => '2026-12-01']),
        ];

        $response = $this->actingAs(User::factory()->create())->getJson("/api/projects?sort={$sort}");

        expect($response->json('data.*.id'))->toBe(array_map(fn (string $key): int => $projects[$key]->id, $expectedOrder));
    })->with([
        'ascending' => ['due_date', ['early', 'late', 'none']],
        'descending' => ['-due_date', ['late', 'early', 'none']],
    ]);

    it('returns 422 when a filter value is invalid', function () {
        $response = $this->actingAs(User::factory()->create())->getJson('/api/projects?status=archived&priority=urgent');

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'status' => 'The status must be one of: planning, in_progress, on_hold, completed.',
            'priority' => 'The priority must be one of: low, medium, high.',
        ]);
    });

    it('returns 422 when the sort column is not allowed', function () {
        $response = $this->actingAs(User::factory()->create())->getJson('/api/projects?sort=id;drop table projects');

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'sort' => 'The sort must be one of: client_name, name, status, priority, start_date, due_date, created_at. Prefix with "-" for descending order.',
        ]);
    });
});

describe('show', function () {
    it('returns the project', function () {
        $project = Project::factory()->create(validProjectPayload());

        $response = $this->actingAs(User::factory()->create())->getJson("/api/projects/{$project->id}");

        $response->assertOk()->assertJson([
            'data' => ['id' => $project->id, ...validProjectPayload()],
        ]);
    });

    it('returns 404 when the project does not exist', function () {
        $response = $this->actingAs(User::factory()->create())->getJson('/api/projects/999');

        $response->assertNotFound()->assertExactJson(['message' => 'Project not found.']);
    });
});

describe('store', function () {
    it('creates a project', function () {
        $response = $this->actingAs(User::factory()->create())->postJson('/api/projects', validProjectPayload());

        $response->assertCreated()->assertJson(['data' => validProjectPayload()]);
        $this->assertDatabaseHas('projects', [
            'id' => $response->json('data.id'),
            'client_name' => 'Acme Corp',
            'name' => 'Website Redesign',
            'status' => 'in_progress',
            'priority' => 'high',
        ]);
    });

    it('creates a project without optional fields', function () {
        $payload = validProjectPayload(['description' => null, 'start_date' => null, 'due_date' => null]);

        $response = $this->actingAs(User::factory()->create())->postJson('/api/projects', $payload);

        $response->assertCreated()->assertJson(['data' => $payload]);
    });

    it('accepts a due date equal to the start date', function () {
        $payload = validProjectPayload(['start_date' => '2026-10-01', 'due_date' => '2026-10-01']);

        $response = $this->actingAs(User::factory()->create())->postJson('/api/projects', $payload);

        $response->assertCreated();
    });

    it('returns 422 when required fields are missing', function () {
        $response = $this->actingAs(User::factory()->create())->postJson('/api/projects', []);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'client_name' => 'The client name field is required.',
            'name' => 'The project name field is required.',
            'status' => 'The status field is required.',
            'priority' => 'The priority field is required.',
        ]);
        $this->assertDatabaseCount('projects', 0);
    });

    it('returns 422 when the status is invalid', function () {
        $response = $this->actingAs(User::factory()->create())
            ->postJson('/api/projects', validProjectPayload(['status' => 'archived']));

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'status' => 'The status must be one of: planning, in_progress, on_hold, completed.',
        ]);
        $this->assertDatabaseCount('projects', 0);
    });

    it('returns 422 when the priority is invalid', function () {
        $response = $this->actingAs(User::factory()->create())
            ->postJson('/api/projects', validProjectPayload(['priority' => 'urgent']));

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'priority' => 'The priority must be one of: low, medium, high.',
        ]);
        $this->assertDatabaseCount('projects', 0);
    });

    it('returns 422 when the due date is before the start date', function () {
        $payload = validProjectPayload(['start_date' => '2026-10-10', 'due_date' => '2026-10-09']);

        $response = $this->actingAs(User::factory()->create())->postJson('/api/projects', $payload);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'due_date' => 'The due date cannot be earlier than the start date.',
        ]);
        $this->assertDatabaseCount('projects', 0);
    });

    it('returns 422 when a date is not in Y-m-d format', function () {
        $response = $this->actingAs(User::factory()->create())
            ->postJson('/api/projects', validProjectPayload(['start_date' => '10/01/2026']));

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'start_date' => 'The start date field must match the format Y-m-d.',
        ]);
    });
});

describe('update', function () {
    it('updates the project', function () {
        $project = Project::factory()->create();
        $payload = validProjectPayload(['status' => 'completed', 'priority' => 'low']);

        $response = $this->actingAs(User::factory()->create())->putJson("/api/projects/{$project->id}", $payload);

        $response->assertOk()->assertJson(['data' => ['id' => $project->id, ...$payload]]);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'completed', 'priority' => 'low']);
    });

    it('returns 422 and keeps the project unchanged when the due date is before the start date', function () {
        $project = Project::factory()->create(validProjectPayload());
        $payload = validProjectPayload(['name' => 'Renamed', 'start_date' => '2026-10-10', 'due_date' => '2026-10-09']);

        $response = $this->actingAs(User::factory()->create())->putJson("/api/projects/{$project->id}", $payload);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'due_date' => 'The due date cannot be earlier than the start date.',
        ]);
        expect($project->fresh()->name)->toBe('Website Redesign');
    });

    it('returns 404 when the project does not exist', function () {
        $this->actingAs(User::factory()->create())
            ->putJson('/api/projects/999', validProjectPayload())
            ->assertNotFound();
    });
});

describe('destroy', function () {
    it('deletes the project', function () {
        $project = Project::factory()->create();

        $response = $this->actingAs(User::factory()->create())->deleteJson("/api/projects/{$project->id}");

        $response->assertNoContent();
        $this->assertModelMissing($project);
    });

    it('returns 404 when the project does not exist', function () {
        $this->actingAs(User::factory()->create())->deleteJson('/api/projects/999')->assertNotFound();
    });
});
