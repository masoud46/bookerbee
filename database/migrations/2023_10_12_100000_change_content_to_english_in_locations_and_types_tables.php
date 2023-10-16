<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		DB::table('locations')
			->where('code', '003')
			->update(['description' => "Insured's residence"]);
		DB::table('locations')
			->where('code', '006')
			->update(['description' => "Assistance and care facility"]);
		DB::table('locations')
			->where('code', '009')
			->update(['description' => "Professional office"]);
		DB::table('locations')
			->where('code', '009b')
			->update(['description' => "Secondary professional office"]);

		DB::table('types')
			->where('code', 'SP01')
			->update(['description' => "Introductory psychotherapy session"]);
		DB::table('types')
			->where('code', 'SP02')
			->update(['description' => "Supportive psychotherapy session"]);
		DB::table('types')
			->where('code', 'SP03')
			->update(['description' => "Extended supportive psychotherapy session"]);
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		DB::table('locations')
			->where('code', '003')
			->update(['description' => "Domicile de l'assuré"]);
		DB::table('locations')
			->where('code', '006')
			->update(['description' => "Établissement d'aides et de soins"]);
		DB::table('locations')
			->where('code', '009')
			->update(['description' => "Cabinet professionnel"]);
		DB::table('locations')
			->where('code', '009b')
			->update(['description' => "Cabinet professionnel secondaire"]);

		DB::table('types')
			->where('code', 'SP01')
			->update(['description' => "Séance de psychothérapie d'initiation"]);
		DB::table('types')
			->where('code', 'SP02')
			->update(['description' => "Séance de psychothérapie de soutien"]);
		DB::table('types')
			->where('code', 'SP03')
			->update(['description' => "Séance de psychothérapie de soutien prolongée"]);
	}
};
