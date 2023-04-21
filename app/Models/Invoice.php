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
	public static function getLastId() {
		$invoice = Invoice::select("id")
			->where("user_id", "=", Auth::user()->id)
			->latest()
			->first();

		return $invoice ? $invoice->id : null;
	}

}
