<?php

namespace App\Models;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use BackedEnum;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['client_name', 'name', 'description', 'status', 'priority', 'start_date', 'due_date'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * The columns the project list can be sorted by.
     *
     * @var list<string>
     */
    public const SORTABLE = ['client_name', 'name', 'status', 'priority', 'start_date', 'due_date', 'created_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'priority' => ProjectPriority::class,
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }

    /**
     * Match projects whose client or project name contains the given term.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $query->where(function (Builder $query) use ($term): void {
            $query->whereLike('client_name', "%{$term}%")
                ->orWhereLike('name', "%{$term}%");
        });
    }

    /**
     * Sort by a column from SORTABLE; a leading "-" sorts in descending order.
     *
     * Status and priority follow their enum order rather than alphabetical
     * order, and projects without a date are always listed last.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function sortBy(Builder $query, string $sort): void
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        match ($column) {
            'status' => $this->orderByEnum($query, $column, ProjectStatus::cases(), $direction),
            'priority' => $this->orderByEnum($query, $column, ProjectPriority::cases(), $direction),
            'start_date', 'due_date' => $query->orderByRaw("{$column} is null")->orderBy($column, $direction),
            default => $query->orderBy($column, $direction),
        };

        $query->orderBy('id', $direction);
    }

    /**
     * Order a column by the position of its value in the given enum cases.
     *
     * @param  Builder<self>  $query
     * @param  list<BackedEnum>  $cases
     */
    protected function orderByEnum(Builder $query, string $column, array $cases, string $direction): void
    {
        $whens = str_repeat('when ? then ? ', count($cases));
        $bindings = collect($cases)->flatMap(fn (BackedEnum $case, int $position): array => [$case->value, $position])->all();

        $query->orderByRaw("case {$column} {$whens}end {$direction}", $bindings);
    }
}
