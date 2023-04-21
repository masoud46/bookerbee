<?php

namespace App\Models;

use App\Traits\HashidsRouteTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Patient extends Model {
	use HasFactory;
	// use HasFactory, HashidsRouteTrait;

	/**
	 * Get the number of patient's previous appointments
	 *
	 * @param  Integer $id
	 * @return Integer
	 */
	public static function getPrevSessions($id, $date = null) {
		$sessions = Invoice::whereUserId(Auth::user()->id)
			->wherePatientId($id)
			->where(function ($query) use ($date) {
				if ($date) {
					$query->where("invoices.created_at", "<", $date);
				}
			})
			->join("appointments", "appointments.invoice_id", "=", "invoices.id")
			->count();

		return $sessions;
	}
}
