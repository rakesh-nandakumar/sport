<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Indoor>
 */
class IndoorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        $hours = [];

        foreach ($days as $day) {
            $hours["{$day}_opening"] = '08:00:00';
            $hours["{$day}_closing"] = '23:00:00';
        }

        return array_merge([
            'title' => $this->faker->sentence(),
            'tags'=> 'Cricket, Futsal, Badminton',
            'location' => $this->faker->city(),
            'email' => $this->faker->companyEmail(),
            'website' => $this->faker->url(),
            'description'=>$this->faker->paragraph(5),
            'contact_number'=>$this->faker->PhoneNumber(),
            'price' => $this->faker->numberBetween(500, 5000),
        ], $hours);
    }
}
