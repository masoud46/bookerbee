<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory {
	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {
		return [
			"invoice_id" => $this->faker->numberBetween(1, 2),
			"location_id" => $this->faker->numberBetween(1, 3),
			"type_id" => $this->faker->numberBetween(1, 3),
			"done_at" => $this->faker->date(),
			"description" => $this->faker->text(25),
			"amount" => $this->faker->numberBetween(100, 150) * 100,
			"insurance" => $this->faker->numberBetween(50, 100) * 100,
		];
	}
}
