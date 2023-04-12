<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory {
	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {
		return [
			'category' => $this->faker->numberBetween(1, 3),
			'code' => $this->faker->unique()->numberBetween(1234567890123, 9876543210987),
			'firstname' => $this->faker->firstName(),
			'lastname' => $this->faker->lastName(),
			'email' => $this->faker->unique()->safeEmail(),
			'phone_country_id' => 129,
			'phone_number' => $this->faker->phoneNumber(),
			'address_line1' => $this->faker->streetAddress(),
			'address_code' => "L-1311",
			'address_city' => "Luxembourg",
			'address_country_id' => 129,
		];
	}
}
