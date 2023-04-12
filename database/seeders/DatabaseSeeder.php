<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
	/**
	 * Seed the application's database.
	 */
	public function run(): void {
		// \App\Models\User::factory(10)->create();

		// \App\Models\User::factory()->create([
		//     'name' => 'Test User',
		//     'email' => 'test@example.com',
		// ]);

		$this->call([CountrySeeder::class]);
		$this->call([LocationSeeder::class]);
		$this->call([TypeSeeder::class]);
		$this->call([UserSeeder::class]);
		$this->call([PatientSeeder::class]);
		$this->call([PatientNotesSeeder::class]);
		$this->call([InvoiceSeeder::class]);
		$this->call([SettingsSeeder::class]);

		Appointment::factory(6)->create();
		Patient::factory(1000)->create();
	}
}
