<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Location;
use App\Models\Settings;
use App\Models\Timezone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller {
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->middleware('auth');
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		//
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create() {
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {
		//
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  \App\Models\User  $user
	 * @return \Illuminate\Http\Response
	 */
	public function show(User $user) {
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function edit() {
		$entries = 'resources/js/pages/user.js';

		$locations = Location::all()->sortBy('code');
		$countries = Country::sortedList();
		$timezones = Timezone::all()->sortBy('offset');

		return view('user', [
			'entries' => $entries,
			'countries' => $countries,
			'timezones' => $timezones,
			'locations' => $locations,
		]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request) {
		session()->flash("active-tab", $request['active-tab']);

		$user = Auth::user();
		$params = $request->all();
		$is_profile = $params['active-tab'] === "profile";
		$user_object = [];

		foreach ($params as $key => $value) {
			$field = explode("-", $key, 2);
			switch ($field[0]) {
				case "user":
					$user_object[$field[1]] = $value;
					break;
			}
		}

		if ($is_profile) {
			$params_rules = [
				'user-timezone' => "required",
				'user-code' => "required|unique:users,code,{$user->id}",
				'user-titles' => "required",
				'user-firstname' => "required",
				'user-lastname' => "required",
				'user-email' => "required|email|unique:users,email,{$user->id}",
				'user-phone_country_id' => "required|numeric",
				'user-phone_number' => "required",
				'user-fax_country_id' => "nullable|numeric",
				'user-bank_account' => "required|size:20",
				'user-bank_swift' => "nullable",
			];

			$params['user-bank_account'] = preg_replace("/\s+/", "", $params['user-bank_account']);

			if ($user_object['fax_number'] === null) {
				$user_object['fax_country_id'] = null;
			}
		} else {
			$params_rules = [
				'user-address_line1' => "required",
				'user-address_code' => "required",
				'user-address_city' => "required",
				'user-address_country_id' => "required|numeric",
				'user-address2_line1' => "nullable",
				'user-address2_code' => "nullable",
				'user-address2_city' => "nullable",
				'user-address2_country_id' => "nullable|numeric",
			];

			if ($user_object['address2_line1'] || $user_object['address2_code'] || $user_object['address2_city']) {
				$params_rules['user-address2_line1'] = "required";
				$params_rules['user-address2_code'] = "required";
				$params_rules['user-address2_city'] = "required";
				$params_rules['user-address2_country_id'] = "required|numeric";
			} else {
				$user_object['address2_line2'] = null;
				$user_object['address2_line3'] = null;
				$user_object['address2_country_id'] = null;
			}
		}

		$params_messages = [
			'user-timezone.required' => app('ERRORS')['required'],
			'user-code.required' => app('ERRORS')['required'],
			'user-code.unique' => app('ERRORS')['unique']['user_code'],
			'user-titles.required' => app('ERRORS')['required'],
			'user-firstname.required' => app('ERRORS')['required'],
			'user-lastname.required' => app('ERRORS')['required'],
			'user-email.required' => app('ERRORS')['required'],
			'user-email.email' => app('ERRORS')['email'],
			'user-email.unique' => app('ERRORS')['unique']['email'],
			'user-phone_number.required' => app('ERRORS')['required'],
			'user-phone_country_id.numeric' => app('ERRORS')['numeric'],
			'user-fax_country_id.numeric' => app('ERRORS')['numeric'],
			'user-bank_account.required' => app('ERRORS')['required'],
			'user-bank_account.size' => app('ERRORS')['iban'],
			'user-address_line1.required' => app('ERRORS')['required'],
			'user-address_code.required' => app('ERRORS')['required'],
			'user-address_city.required' => app('ERRORS')['required'],
			'user-address_country_id.required' => app('ERRORS')['required'],
			'user-address_country_id.numeric' => app('ERRORS')['numeric'],
			'user-address2_line1.required' => app('ERRORS')['required'],
			'user-address2_code.required' => app('ERRORS')['required'],
			'user-address2_city.required' => app('ERRORS')['required'],
			'user-address2_country_id.required' => app('ERRORS')['required'],
			'user-address2_country_id.numeric' => app('ERRORS')['numeric'],
		];

		$validator = Validator::make($params, $params_rules, $params_messages);

		if ($validator->fails()) {
			session()->flash("error", app('ERRORS')['form']);
			return back()->withErrors($validator->errors())->withInput();
		}

		foreach ($user_object as $key => $value) {
			$user[$key] = $value;
		}

		if ($is_profile) {
			$user['firstname'] = ucfirst($user['firstname']);
			$user['lastname'] = ucfirst($user['lastname']);
			$user['titles'] = json_encode(
				array_values( // remove keys, get only the values
					array_filter( // remove empty rows (preserves keys)
						array_map( // trim each row
							"trim",
							preg_split('/[\r\n]+/', $user['titles'], -1, PREG_SPLIT_NO_EMPTY)
						)
					)
				),
				JSON_UNESCAPED_UNICODE
			);
			$user['bank_account'] = preg_replace("/\s+/", "", $user['bank_account']);
			$user['bank_account'] = implode(" ", str_split($user['bank_account'], 4));
			$user['bank_account'] = strtoupper($user['bank_account']);
			$user['bank_swift'] = $user['bank_swift'] ? strtoupper($user['bank_swift']) : null;
		} else if (!$user->address2_line1) {
			$settings = Settings::whereUserId($user->id)->first();
			$locations = Location::all()->sortBy('code');
			$locations = array_column($locations->toArray(), 'code', 'id');

			if ($locations[$settings->location] === "009b") {
				$location = array_search('009', $locations);
				$settings->location = $location === false
					? array_key_first($locations)
					: $location;
				$settings->save();
			}
		}

		$user->save();

		session()->flash(
			"success",
			$is_profile
				? __("Your profile has been updated.")
				: __("Your address has been updated.")
		);
		return redirect()->route("profile");
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Models\User  $user
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(User $user) {
		//
	}

	// Signed URL test
	public function sign($id, $email) {
		dd(\Illuminate\Support\Facades\URL::signedRoute('unsubscribe', ['id' => $id, 'email' => $email]));
	}

	public function ss() {
		echo "<pre>";
		print_r(session()->all());
	}
}
