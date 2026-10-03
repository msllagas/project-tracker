<?php

namespace App\Http\Requests;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'priority' => ['required', Rule::enum(ProjectPriority::class)],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'due_date' => [
                'nullable',
                'date_format:Y-m-d',
                Rule::when($this->filled('start_date'), 'after_or_equal:start_date'),
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'project name',
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
            'start_date.date_format' => 'The start date must be a valid date in the YYYY-MM-DD format.',
            'due_date.date_format' => 'The due date must be a valid date in the YYYY-MM-DD format.',
            'due_date.after_or_equal' => 'The due date cannot be earlier than the start date.',
        ];
    }
}
