<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		DB::table('types')->insert([
			['id' => 1, 'code' => "SP01", 'description' => "Séance de psychothérapie d’initiation", 'max_sessions' => 3],
			['id' => 2, 'code' => "SP02", 'description' => "Séance de psychothérapie de soutien", 'max_sessions' => 24],
			['id' => 3, 'code' => "SP03", 'description' => "Séance de psychothérapie de soutien prolongée", 'max_sessions' => null],
		]);
	}
}
