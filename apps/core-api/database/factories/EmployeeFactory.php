<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Models\Employee;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'employee_number' => fake()->unique()->numerify('##################'),
            'full_name' => fake()->name(),
            'gender' => fake()->randomElement(['L', 'P']),
            'employment_type' => EmploymentType::Gty,
            'is_teacher' => true,
        ];
    }
}
