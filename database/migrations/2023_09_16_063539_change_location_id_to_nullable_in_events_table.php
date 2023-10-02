<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::table('events', function (Blueprint $table) {
			$table->bigInteger('location_id')->nullable()->change();
		});

		// DB::table('events')->where('location_id', '=', 3)->update(['location_id' => null]);
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		DB::table('events')->whereNull('location_id')->update(['location_id' => 3]);

		Schema::table('events', function (Blueprint $table) {
			$table->bigInteger('location_id')->nullable(false)->change();
		});
	}
};
