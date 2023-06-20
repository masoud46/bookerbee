<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
	use HasApiTokens, HasFactory, Notifiable;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'firstname',
		'lastname',
		'email',
		'password',
	];

	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var array<int, string>
	 */
	protected $hidden = [
		'password',
		'remember_token',
	];

	/**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'email_verified_at' => 'datetime',
	];

	/**
	 * Get the user's enabled features as an array.
	 */
	protected function features(): Attribute {
		return Attribute::make(
			// get: fn ($value) => $value ? explode(',', $value) : [],
			get: function ($value) {
				$features = [];

				if ($value) {
					foreach ($features = explode(",", $value) as $index => $feature) {
						$features[$index] = trim($feature);
					}
				}
			
				return $features;
			},
		);
	}

	/**
	 * Check if user has a secondary address.
	 *
	 * @return Boolean
	 */
	public static function hasSecondaryAddress() {
		$user = Auth::user();

		return
			$user->address2_line1 &&
			$user->address2_code &&
			$user->address2_city &&
			$user->address2_country_id;
	}

}
