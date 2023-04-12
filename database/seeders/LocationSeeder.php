<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		DB::table('locations')->insert([
			['id' => 1, 'code' => "003", 'description' => "Domicile de l'assuré"],
			['id' => 2, 'code' => "006", 'description' => "Etablissement d'aides et de soins"],
			['id' => 3, 'code' => "009", 'description' => "Cabinet professionnel"],
			['id' => 4, 'code' => "009b", 'description' => "Cabinet professionnel secondaire"],
		]);
	}
}
