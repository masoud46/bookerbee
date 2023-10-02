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
		Schema::table('patients', function (Blueprint $table) {
			$table->string('code', 20)->nullable()->change();
			$table->string('address_line1')->nullable()->change();
			$table->string('address_code', 20)->nullable()->change();
			$table->string('address_city', 100)->nullable()->change();
			$table->integer('address_country_id')->nullable()->change();
			$table->string('locale', 2)->nullable()->change();
		});

		DB::table('patients')->where('code', '=', '')->update(['code' => null]);
		DB::table('patients')->where('address_line1', '=', '')->update(['address_line1' => null]);
		DB::table('patients')->where('address_code', '=', '')->update(['address_code' => null]);
		DB::table('patients')->where('address_city', '=', '')->update(['address_city' => null]);
		DB::table('patients')->where('address_country_id', '=', 0)->update(['address_country_id' => null]);
		DB::table('patients')->where('locale', '=', '')->update(['locale' => null]);
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		DB::table('patients')->whereNull('code')->update(['code' => '']);
		DB::table('patients')->whereNull('address_line1')->update(['address_line1' => '']);
		DB::table('patients')->whereNull('address_code')->update(['address_code' => '']);
		DB::table('patients')->whereNull('address_city')->update(['address_city' => '']);
		DB::table('patients')->whereNull('address_country_id')->update(['address_country_id' => 0]);
		DB::table('patients')->whereNull('locale')->update(['locale' => '']);

		Schema::table('patients', function (Blueprint $table) {
			$table->string('code', 20)->nullable(false)->change();
			$table->string('address_line1', 100)->nullable(false)->change();
			$table->string('address_code', 20)->nullable(false)->change();
			$table->string('address_city', 50)->nullable(false)->change();
			$table->integer('address_country_id')->nullable(false)->change();
			$table->string('locale', 2)->nullable(false)->change();
		});
	}
};
