<?php

namespace App\Models;

use App\Traits\HashidsRouteTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Invoice extends Model {
	use HasFactory;
	// use HasFactory, HashidsRouteTrait;

	/**
	 * Get the id of the user's last invoice.
	 *
	 * @return Integer
	 */
	public static function getLastId($patient_id) {
		$invoice = Invoice::select("id")
			->whereUserId(Auth::user()->id)
			->wherePatientId($patient_id)
			->latest()
			->first();

		return $invoice ? $invoice->id : null;
	}

}
