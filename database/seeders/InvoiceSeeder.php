<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		DB::table('invoices')->insert([
			[
				"user_id" => 2,
				"patient_id" => 1,
				"name" => "TIERE, Un",
				"acc_number" => Str::random(10),
				"acc_date" => "2023-02-01",
				"doc_code" => Str::random(10),
				"doc_name" => "M. Serge MOUCHERON",
				"doc_date" => "2023-02-01",
				"prepayment" => 1000,
				"granted_at" => "2023-02-01",
				"location_check" => true,
				"location_name" => "Domicile",
				"location_address" => "Adresse de domicile",
				"location_code" => "L-1120",
				"location_city" => "Luxembourg",
				"location_country_id" => 129,
			], [
				"user_id" => 2,
				"patient_id" => 2,
				"name" => "SMITH, John",
				"acc_number" => Str::random(10),
				"acc_date" => "2023-03-15",
				"doc_code" => Str::random(10),
				"doc_name" => "Dr. Zofia WITTBRODT ",
				"doc_date" => "2023-03-15",
				"prepayment" => null,
				"granted_at" => "2023-03-15",
				"location_check" => false,
				"location_name" => null,
				"location_address" => null,
				"location_code" => null,
				"location_city" => null,
				"location_country_id" => null,
			],
		]);
	}
}
