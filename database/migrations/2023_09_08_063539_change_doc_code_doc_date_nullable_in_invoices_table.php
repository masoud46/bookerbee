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
		Schema::table('invoices', function (Blueprint $table) {
			$table->string('doc_code', 20)->nullable()->change();
			$table->date('doc_date')->useCurrent()->nullable()->change();
		});

		// DB::table('invoices')->where('doc_code', '=', '')->update(['doc_code' => null]);
		// DB::table('invoices')->where('doc_date', '=', '1970-01-01')->update(['doc_date' => null]);
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		// DB::table('invoices')->whereNull('doc_code')->update(['doc_code' => '']);
		// DB::table('invoices')->whereNull('doc_date')->update(['doc_date' => '1970-01-01']);

		// Schema::table('invoices', function (Blueprint $table) {
		// 	$table->string('doc_code', 20)->nullable(false)->change();
		// 	$table->date('doc_date')->useCurrent()->nullable(false)->change();
		// });
	}
};
