<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Type extends Model {
	use HasFactory;

	/**
	 * Get the type's translated description.
	 */
	protected function description(): Attribute {
		return Attribute::make(
			get: fn (string $value) => __($value),
		);
	}
}
