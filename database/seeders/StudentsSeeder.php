<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;

class StudentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 5; $i++) {
            Student::create([
                'firstname' => $faker -> firstName(),
                'lastname' => $faker -> lastName(),
                'email' => $faker -> email(),
                'address' => $faker -> sentence(),
                'score' => $faker -> randomFloat(2, 0, 100),
            ]);
        }
    }
}
