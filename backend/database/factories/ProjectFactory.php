<?php

namespace Database\Factories;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = Carbon::instance(fake()->dateTimeBetween('-3 months', '+1 month'));

        return [
            'client_name' => fake()->company(),
            'name' => fake()->randomElement([
                'Website Redesign',
                'Mobile App Launch',
                'Brand Refresh',
                'SEO Audit',
                'E-commerce Migration',
                'Marketing Landing Page',
                'Customer Portal',
                'Email Campaign Templates',
                'Analytics Dashboard',
                'CMS Integration',
            ]),
            'description' => fake()->optional()->sentence(12),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'priority' => fake()->randomElement(ProjectPriority::cases()),
            'start_date' => $startDate,
            'due_date' => $startDate->copy()->addDays(fake()->numberBetween(7, 120)),
        ];
    }
}
