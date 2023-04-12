<?php

namespace App\Traits;

use Hashids;

trait HashidsRouteTrait {
	public function resolveRouteBinding($value, $field = null) {
		$decoded = Hashids::decode($value);

		if (count($decoded) > 0) {
			return parent::resolveRouteBinding($decoded[0], $field);
		}

		abort(404);
	}

	public function getRouteKeyName() {
		return 'id';
	}

	public function getRouteKey() {
		return Hashids::encode($this->getKey());
	}
}
