<?php

namespace App\Http\Requests;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProjectsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
            'priority' => ['nullable', Rule::enum(ProjectPriority::class)],
            'sort' => ['nullable', Rule::in($this->sortOptions())],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.enum' => 'The status must be one of: '.implode(', ', array_column(ProjectStatus::cases(), 'value')).'.',
            'priority.enum' => 'The priority must be one of: '.implode(', ', array_column(ProjectPriority::cases(), 'value')).'.',
            'sort.in' => 'The sort must be one of: '.implode(', ', Project::SORTABLE).'. Prefix with "-" for descending order.',
        ];
    }

    /**
     * Get every accepted sort value, in ascending and descending form.
     *
     * @return list<string>
     */
    protected function sortOptions(): array
    {
        return [
            ...Project::SORTABLE,
            ...array_map(fn (string $column): string => "-{$column}", Project::SORTABLE),
        ];
    }
}
