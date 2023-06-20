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
		DB::statement('ALTER TABLE events CHANGE reminder reminder_email INT(4)');

		Schema::table('events', function (Blueprint $table) {
			$table
				->boolean('reminder_sms')
				->after('reminder_email')
				->default(false)
				->comment('sms reminder sent');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::table('events', function (Blueprint $table) {
			$table->dropColumn('reminder_sms');
		});

		DB::statement('ALTER TABLE events CHANGE reminder_email reminder INT(4)');
	}
};
