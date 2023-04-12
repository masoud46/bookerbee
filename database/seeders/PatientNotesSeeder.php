<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientNotesSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		DB::table('patient_notes')->insert([
			[
				'id' => 1,
				'user_id' => 2,
				'patient_id' => 1,
				'notes' => "15/01/2023
Lorem ipsum dolor sit amet consectetur adipisicing elit. Quo laborum tenetur voluptatum et, molestias asperiores vitae commodi fuga obcaecati porro sit, id vero ad ex similique. Tempore quod quia illum.


09/03/2023
Lo numquam cumque adipisci deleniti tempora ad. Delectus, tempore maxime qui sapiente sit provident possimus, ipsa reprehenderit dolor adipisci minima sunt placeat.\n\nLorem ipsum dolor sit amet consectetur adipisicing elit. Quo laborum tenetur voluptatum et, molestias asperiores vitae commodi fuga obcaecati porro sit, id vero ad ex similique. Tempore quod quia illum.",
				'created_at' => '2023-01-15 14:23:00.000',
				'updated_at' => '2023-01-15 14:23:00.000',
			],
		]);
	}
}
