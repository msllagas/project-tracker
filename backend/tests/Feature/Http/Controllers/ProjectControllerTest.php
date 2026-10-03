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

it('returns 404 for project ids that are not positive integers', function (string $method, string $id) {
    $response = $this->actingAs(User::factory()->create())->json($method, "/api/projects/{$id}", validProjectPayload());

    $response->assertNotFound()->assertExactJson(['message' => 'Project not found.']);
})->with(['show' => 'GET', 'update' => 'PUT', 'delete' => 'DELETE'])->with([
    'text' => 'abc',
    'zero' => '0',
    'too large for the column' => '99999999999999999999',
]);

it('returns 429 after 60 requests a minute from the same user', function () {
    $this->actingAs(User::factory()->create());

    foreach (range(1, 60) as $attempt) {
        $this->getJson('/api/projects')->assertOk();
    }

    $this->getJson('/api/projects')->assertTooManyRequests()->assertHeader('Retry-After');
});

describe('index', function () {
    it('lists the projects with pagination details', function () {
        $projects = Project::factory()->count(3)->create();

        $response = $this->actingAs(User::factory()->create())->getJson('/api/projects');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJson(['meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 10, 'total' => 3]]);
        expect($response->json('data.*.id'))->toEqualCanonicalizing($projects->pluck('id')->all());
    });

    it('lists 10 projects per page by default', function () {
        Project::factory()->count(12)->create();

        $response = $this->actingAs(User::factory()->create())->getJson('/api/projects');

        $response->assertJsonCount(10, 'data')
            ->assertJson(['meta' => ['per_page' => 10, 'last_page' => 2, 'total' => 12]]);
    });

    it('returns the requested page with the requested page size', function () {
        $projects = collect(range(1, 7))
            ->map(fn (int $day) => Project::factory()->create(['created_at' => now()->subDays($day)]));

        $response = $this->actingAs(User::factory()->create())->getJson('/api/projects?per_page=3&page=2');

        $response->assertJson(['meta' => ['current_page' => 2, 'last_page' => 3, 'from' => 4, 'to' => 6, 'total' => 7]]);
        expect($response->json('data.*.id'))->toBe($projects->slice(3, 3)->pluck('id')->values()->all());
    });

    it('keeps the filters in the page links', function () {
        Project::factory()->count(3)->create(['status' => 'planning']);

        $response = $this->actingAs(User::factory()->create())->getJson('/api/projects?status=planning&per_page=1');

        expect($response->json('links.next'))->toContain('status=planning')->toContain('per_page=1')->toContain('page=2');
    });

    it('returns 422 when the page or page size is invalid', function (string $query, array $errors) {
        $response = $this->actingAs(User::factory()->create())->getJson("/api/projects?{$query}");

        $response->assertUnprocessable()->assertJsonValidationErrors($errors);
    })->with([
        'page below 1' => ['page=0', ['page' => 'The page field must be at least 1.']],
        'page size of 0' => ['per_page=0', ['per_page' => 'The per page field must be between 1 and 100.']],
        'page size above 100' => ['per_page=101', ['per_page' => 'The per page field must be between 1 and 100.']],
        'text page size' => ['per_page=all', ['per_page' => 'The per page field must be an integer.']],
    ]);

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

    it('returns 422 when a date is not a valid Y-m-d date', function (string $date) {
        $response = $this->actingAs(User::factory()->create())
            ->postJson('/api/projects', validProjectPayload(['start_date' => $date, 'due_date' => $date]));

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'start_date' => 'The start date must be a valid date in the YYYY-MM-DD format.',
            'due_date' => 'The due date must be a valid date in the YYYY-MM-DD format.',
        ]);
    })->with([
        'other format' => '10/01/2026',
        'impossible date' => '2026-02-30',
    ]);
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

    it('returns 422 when required fields are missing', function () {
        $project = Project::factory()->create();

        $response = $this->actingAs(User::factory()->create())->putJson("/api/projects/{$project->id}", []);

        $response->assertUnprocessable()->assertJsonValidationErrors([
            'client_name' => 'The client name field is required.',
            'name' => 'The project name field is required.',
            'status' => 'The status field is required.',
            'priority' => 'The priority field is required.',
        ]);
    });

    it('returns 422 when a field is invalid', function (array $overrides, array $errors) {
        $project = Project::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->putJson("/api/projects/{$project->id}", validProjectPayload($overrides));

        $response->assertUnprocessable()->assertJsonValidationErrors($errors);
    })->with([
        'status' => [['status' => 'archived'], ['status' => 'The status must be one of: planning, in_progress, on_hold, completed.']],
        'priority' => [['priority' => 'urgent'], ['priority' => 'The priority must be one of: low, medium, high.']],
        'date' => [['start_date' => '2026-02-30'], ['start_date' => 'The start date must be a valid date in the YYYY-MM-DD format.']],
    ]);

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

describe('list cache', function () {
    it('keeps serving the cached list until a project changes through the API', function () {
        $project = Project::factory()->create(['name' => 'Website Redesign']);
        $this->actingAs(User::factory()->create())->getJson('/api/projects');

        // Changes made outside the API do not clear the cache, and it never expires.
        $project->updateQuietly(['name' => 'Changed Directly']);
        $this->travel(1)->year();

        expect($this->getJson('/api/projects')->json('data.0.name'))->toBe('Website Redesign');
    });

    it('caches each page and filter separately', function () {
        Project::factory()->create(['status' => 'planning']);
        Project::factory()->count(2)->create(['status' => 'completed']);
        $this->actingAs(User::factory()->create());

        $this->getJson('/api/projects?status=planning')->assertJsonCount(1, 'data');
        $this->getJson('/api/projects?status=completed')->assertJsonCount(2, 'data');
        $this->getJson('/api/projects?status=completed&per_page=1&page=2')->assertJsonCount(1, 'data');
    });

    it('refreshes the list after a project is created', function () {
        $this->actingAs(User::factory()->create())->getJson('/api/projects')->assertJsonCount(0, 'data');

        $this->postJson('/api/projects', validProjectPayload())->assertCreated();

        $this->getJson('/api/projects')->assertJsonCount(1, 'data');
    });

    it('refreshes the list after a project is updated', function () {
        $project = Project::factory()->create();
        $this->actingAs(User::factory()->create())->getJson('/api/projects');

        $this->putJson("/api/projects/{$project->id}", validProjectPayload(['name' => 'Renamed']))->assertOk();

        expect($this->getJson('/api/projects')->json('data.0.name'))->toBe('Renamed');
    });

    it('refreshes the list after a project is deleted', function () {
        $project = Project::factory()->create();
        $this->actingAs(User::factory()->create())->getJson('/api/projects')->assertJsonCount(1, 'data');

        $this->deleteJson("/api/projects/{$project->id}")->assertNoContent();

        $this->getJson('/api/projects')->assertJsonCount(0, 'data');
    });
});
