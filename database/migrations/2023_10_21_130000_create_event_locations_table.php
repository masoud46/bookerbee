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
		Schema::create('event_locations', function (Blueprint $table) {
			$table->bigInteger('event_id')->unsigned()->unique();
			$table->string('name', 100)->nullable();
			$table->string('address', 255)->nullable();
			$table->string('code', 10)->nullable();
			$table->string('city', 100)->nullable();
			$table->integer('country_id')->unsigned()->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists('event_locations');
	}
};
