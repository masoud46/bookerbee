<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::create('users', function (Blueprint $table) {
			$table->id();
			$table->string('timezone', 100);
			$table->string('email')->unique();
			$table->string('password');
			$table->string('firstname');
			$table->string('lastname');
			$table->string('code', 20)->unique();
			$table->string('titles');
			$table->integer('phone_country_id');
			$table->string('phone_number', 20);
			$table->integer('fax_country_id')->nullable();
			$table->string('fax_number', 20)->nullable();
			$table->string('address_line1', 100);
			$table->string('address_line2', 100)->nullable();
			$table->string('address_line3', 100)->nullable();
			$table->string('address_code', 10);
			$table->string('address_city', 50);
			$table->integer('address_country_id');
			$table->string('address2_line1', 100)->nullable();
			$table->string('address2_line2', 100)->nullable();
			$table->string('address2_line3', 100)->nullable();
			$table->string('address2_code', 10)->nullable();
			$table->string('address2_city', 50)->nullable();
			$table->integer('address2_country_id')->nullable();
			$table->string('bank_account', 50);
			$table->string('bank_swift', 20)->nullable();
			$table->timestamp('created_at')->useCurrent();
			$table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
			$table->rememberToken();
			$table->timestamp('email_verified_at')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists('users');
	}
};
