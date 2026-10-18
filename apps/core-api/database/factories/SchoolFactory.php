<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        return [
            'npsn' => fake()->unique()->numerify('########'),
            'name' => 'SD '.fake()->city(),
            'level' => 'SD',
            'city' => 'Nabire',
            'province' => 'Papua Tengah',
            'timezone' => 'Asia/Jayapura',
        ];
    }
}
