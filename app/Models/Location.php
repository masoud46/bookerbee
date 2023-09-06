<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model {
	use HasFactory;

	/**
	 * Get locations according to user's secondary address.
	 *
	 * @return Collection
	 */
	public static function fetchAll() {
		$locations = Location::all()->sortBy('code');

		if (!User::hasSecondaryAddress()) {
			$bisId = array_search("009b", array_column($locations->toArray(), "code"));
			$locations[$bisId]['disabled'] = true;
		}
		
		return $locations;
	}
}
