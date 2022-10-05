<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $faker = \Faker\Factory::create();

        return [
            'firstname' => $faker -> firstName(),
            'lastname' => $faker -> lastName(),
            'email' => $faker -> email(),
            'address' => $faker -> sentence(),
            'score' => $faker -> randomFloat(2, 0, 100),
        ];
    }
}
