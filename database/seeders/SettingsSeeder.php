<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		DB::table('settings')->insert([
			[
				'id' => 1,
				'user_id' => 1,
				'amount' => 15129,
				'location' => 3,
			], [
				'id' => 2,
				'user_id' => 2,
				'amount' => 15129,
				'location' => 3,
			]
		]);
	}
}
