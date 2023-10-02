<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserToken extends Model {
	use HasFactory;

	/**
	 * Encrypt and insert a token for an data-action pair.
	 *
	 * @param  Int $user_id
	 * @param  String $data
	 * @param  String $action
	 * @return Array $result
	 */
	public static function create(int $user_id, string $data, string $action, string $token) {
		$result = ['success' => false];

		try {
			$userToken = new UserToken();

			$userToken->user_id = $user_id;
			$userToken->data = $data;
			$userToken->action = $action;
			$userToken->token = Hash::make($token);

			$userToken->save();

			$result['success'] = true;
		} catch (\Throwable $th) {
			$result['error'] = $th->__toString();
		}

		return $result;
	}

	/**
	 * Verify a token and it's expiration for a data-action pair.
	 *
	 * @param  String $data
	 * @param  String $action
	 * @param  String $token
	 * @return Array $result
	 */
	public static function verify(string $data, string $action, string $token) {
		$result = ['success' => false];

		function setResult(&$result, $userToken) {
			DB::delete('delete from user_tokens where user_id=? and action=?;', [
				$userToken->user_id,
				$userToken->action
			]);

			$result['id'] = $userToken->user_id;
			$result['success'] = true;
		}

		try {
			$userToken = self::select(['user_id', 'data', 'action', 'token', 'updated_at'])
				->whereData($data)
				->whereAction($action)
				->latest('updated_at')
				->first();

			if ($userToken) {
				$now = Carbon::now();
				$time = Carbon::parse($userToken->updated_at)
					->addMinutes(config('project.token_expiration_time'));

				$expired = $time->lessThan($now);
				$valid = Hash::check($token, $userToken->token);

				if ($valid) {
					if ($expired) {
						$result['error'] = 'expired';
						$result['time'] = $time;
					} else {
						switch ($action) {
							case 'change-email':
								setResult($result, $userToken);
								break;
							case 'change-phone':
								$phone = json_decode($data, true);

								if (isset($phone['country_id']) && isset($phone['number'])) {
									setResult($result, $userToken);
								} else {
									$result['error'] = 'format';
								}
								break;
						}
					}
				} else {
					$result['error'] = 'invalid';
				}
			} else {
				$result['error'] = 'not found';
			}
		} catch (\Throwable $th) {
			$result['error'] = $th->__toString();
		}

		return $result;
	}

	/**
	 * Delete a data-action-token row.
	 *
	 * @param  String $data
	 * @param  String $action
	 * @param  String $token
	 * @return Array $result
	 */
	public static function deleteRow(string $data, string $action, string $token) {
		try {
			$userToken = self::whereData($data)
				->whereAction($action)
				->whereToken($token);

			if ($userToken) {
				$userToken->delete();
			}
		} catch (\Throwable $th) {
		}
	}
}
