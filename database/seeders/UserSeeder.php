<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		DB::table('users')->insert([
			[
				'id' => 1,
				'email' => "psychotherapeute@wittbrodt.lu",
				'password' => Hash::make("Chouchou1969"),
				'firstname' => "J. Ewa",
				'lastname' => "Wittbrodt",
				'code' => "530074-66",
				'titles' => '["Psychothérapeute", "Psychologue Clinicienne", "Sexologue Clinicienne"]',
				'phone_country_id' => 129,
				'phone_number' => "691 246 027",
				'phone_country_id' => 129,
				'phone_number' => "621 522 932",
				'address_line1' => "Avenue Port Neuve 12",
				'address_line2' => null,
				'address_line3' => null,
				'address_code' => "L-2227",
				'address_city' => "Luxembourg",
				'address_country_id' => 129,
				'address2_line1' => "Rue du Trône 24",
				'address2_line2' => "2e étage",
				'address2_line3' => null,
				'address2_code' => "1000",
				'address2_city' => "Bruxelles",
				'address2_country_id' => 22,
				'bank_account' => "LU63 0030 5882 4672 0000",
				'bank_swift' => "BGLLLULL",
				'remember_token' => Str::random(10),
				'email_verified_at' => now(),
			], [
				'id' => 2,
				'email' => "masoudf46@gmail.com",
				'password' => Hash::make("Goiheeh1"),
				'firstname' => "Masoud",
				'lastname' => "Fathi",
				'code' => "530074-67",
				'titles' => '["Psychothérapeute", "Psychologue Clinicienne", "Sexologue Clinicienne"]',
				'phone_country_id' => 129,
				'phone_number' => "621 522 932",
				'address_line1' => "Avenue Port Neuve 12",
				'address_line2' => null,
				'address_line3' => null,
				'address_code' => "L-2227",
				'address_city' => "Luxembourg",
				'address_country_id' => 129,
				'address2_line1' => "Rue du Trône 24",
				'address2_line2' => "2e étage",
				'address2_line3' => null,
				'address2_code' => "1000",
				'address2_city' => "Bruxelles",
				'address2_country_id' => 22,
				'bank_account' => "LU63 0030 5882 4672 0000",
				'bank_swift' => "BGLLLULL",
				'remember_token' => Str::random(10),
				'email_verified_at' => now(),
			],
		]);
	}
}
