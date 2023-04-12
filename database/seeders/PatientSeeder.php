<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		DB::table('patients')->insert([
			[
				'id' => 1,
				'category' => 2,
				'code' => "0000000000000",
				'firstname' => "John",
				'lastname' => "Doe",
				'email' => "jhon.doe@home.net",
				'phone_country_id' => 22,
				'phone_number' => "621 654 987",
				'address_line1' => "Avenue de la Couronne, 1A",
				'address_line2' => null,
				'address_line3' => null,
				'address_code' => "1050",
				'address_city' => "Ixelles",
				'address_country_id' => 22,
			], [
				'id' => 2,
				'category' => 1,
				'code' => "0000000000001",
				'firstname' => "John",
				'lastname' => "Smith",
				'email' => null,
				'phone_country_id' => null,
				'phone_number' => null,
				'address_line1' => "Rue du Trône, 24",
				'address_line2' => "2e étage",
				'address_line3' => null,
				'address_code' => "L-1234",
				'address_city' => "Luxembourg",
				'address_country_id' => 129,
			],
		]);
	}
}
