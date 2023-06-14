<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::table('settings', function (Blueprint $table) {
			$table
				->time('cal_min_time')
				->after('location')
				->default('08:00:00')
				->comment('calendar start time');
			$table
				->time('cal_max_time')
				->after('cal_min_time')
				->default('20:00:00')
				->comment('calendar end time');
			$table
				->tinyInteger('cal_slot')
				->after('cal_max_time')
				->default(30)
				->comment('calendar time slot length in minutes');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::table('settings', function (Blueprint $table) {
			$table->dropColumn('cal_min_time');
			$table->dropColumn('cal_max_time');
			$table->dropColumn('cal_slot');
		});
	}
};
