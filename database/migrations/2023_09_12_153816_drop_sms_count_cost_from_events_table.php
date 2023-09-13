<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::table('events', function (Blueprint $table) {
			$table->dropColumn('sms_count');
			$table->dropColumn('sms_cost');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::table('events', function (Blueprint $table) {
			$table
				->tinyInteger('sms_count')
				->unsigned()
				->after('reminder_sms')
				->default('0');
			$table
				->tinyInteger('sms_cost')
				->unsigned()
				->after('sms_count')
				->default('0');
		});
	}
};
