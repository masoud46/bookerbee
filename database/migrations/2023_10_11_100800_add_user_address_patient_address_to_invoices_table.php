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
			$table->string('user_address')->after('patient_id')->nullable();
			$table->string('patient_address')->after('user_address')->nullable();
		});

		$countries = array_column(
			DB::table('countries')->select(['id', 'name'])->get()->toArray(),
			'name',
			'id'
		);

		$invoices = DB::table('invoices')
			->select([
				'invoices.id',
				'locations.code AS location',
				'users.address_line1 AS user_address_line1',
				'users.address_line2 AS user_address_line2',
				'users.address_line3 AS user_address_line3',
				'users.address_code AS user_address_code',
				'users.address_city AS user_address_city',
				'users.address_country_id AS user_address_country_id',
				'users.address2_line1 AS user_address2_line1',
				'users.address2_line2 AS user_address2_line2',
				'users.address2_line3 AS user_address2_line3',
				'users.address2_code AS user_address2_code',
				'users.address2_city AS user_address2_city',
				'users.address2_country_id AS user_address2_country_id',
				'patients.address_line1 AS patient_address_line1',
				'patients.address_line2 AS patient_address_line2',
				'patients.address_line3 AS patient_address_line3',
				'patients.address_code AS patient_address_code',
				'patients.address_city AS patient_address_city',
				'patients.address_country_id AS patient_address_country_id',
			])
			->join('sessions', 'sessions.invoice_id', '=', 'invoices.id')
			->join('locations', 'locations.id', '=', 'sessions.location_id')
			->join('users', 'users.id', '=', 'invoices.user_id')
			->join('patients', 'patients.id', '=', 'invoices.patient_id')
			->get()
			->groupBy('id');

		foreach ($invoices as $invoice) {
			$invoice = $invoice[0];
			$user_address = makeInvoiceAddress([
				'line1' => $invoice->user_address_line1,
				'line2' => $invoice->user_address_line2,
				'line3' => $invoice->user_address_line3,
				'code' => $invoice->user_address_code,
				'city' => $invoice->user_address_city,
				'country' => $countries[$invoice->user_address_country_id],
			]);
			$patient_address = makeInvoiceAddress([
				'line1' => $invoice->patient_address_line1,
				'line2' => $invoice->patient_address_line2,
				'line3' => $invoice->patient_address_line3,
				'code' => $invoice->patient_address_code,
				'city' => $invoice->patient_address_city,
				'country' => $countries[$invoice-> patient_address_country_id],
			]);

			DB::table('invoices')
				->where('id', $invoice->id)
				->update([
					'user_address' => $user_address,
					'patient_address' => $patient_address,
				]);
		}
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::table('invoices', function (Blueprint $table) {
			$table->dropColumn('user_address');
			$table->dropColumn('patient_address');
		});
	}
};
