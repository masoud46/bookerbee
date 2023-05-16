<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::create('timezones', function (Blueprint $table) {
			$table->id();
			$table->integer('offset', 2);
			$table->string('offset_str', 6)->nullable();
			$table->string('name', 100);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists('timezones');
	}
};
