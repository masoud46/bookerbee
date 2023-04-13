<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Location;
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

		return view('user', [
			'entries' => $entries,
			'countries' => $countries,
			'locations' => $locations,
		]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Models\User  $user
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request) {
		$user = User::find(Auth::user()->id); // to be able to call save()

		$params = $request->all();
		$user_object = [];

		foreach ($params as $key => $value) {
			$field = explode("-", $key, 2);
			switch ($field[0]) {
				case "user":
					$user_object[$field[1]] = $value;
					break;
			}
		}

		$params_rules = [
			'user-code' => "required|unique:users,code,{$user->id}",
			'user-titles' => "required",
			'user-firstname' => "required",
			'user-lastname' => "required",
			'user-email' => "required|email|unique:users,email,{$user->id}",
			'user-phone_country_id' => "required|numeric",
			'user-phone_number' => "required",
			'user-fax_country_id' => "nullable|numeric",
			'user-address_line1' => "required",
			'user-address_code' => "required",
			'user-address_city' => "required",
			'user-address_country_id' => "required|numeric",
			'user-address2_line1' => "nullable",
			'user-address2_code' => "nullable",
			'user-address2_city' => "nullable",
			'user-address2_country_id' => "nullable|numeric",
		];

		$params_messages = [
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

		if ($user_object['fax_number'] === null) {
			$user_object['fax_country_id'] = null;
		}

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

		$validator = Validator::make($params, $params_rules, $params_messages);

		if ($validator->fails()) {
			session()->flash("error", app('ERRORS')['form']);
			return back()->withErrors($validator->errors())->withInput();
		}

		foreach ($user_object as $key => $value) {
			$user[$key] = $value;
		}

		$user['titles'] = json_encode(
			array_values(// remove keys, get only the values
				array_filter( // remove empty rows (preserves keys)
					array_map( // trim each row
						"trim",
						preg_split('/[\r\n]+/', $user['titles'], -1, PREG_SPLIT_NO_EMPTY)
					)
				)
			),
			JSON_UNESCAPED_UNICODE
		);

		$user->save();

		session()->flash("success", __("Your profile has been updated."));
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

	public function ss() {
		echo "<pre>";
		print_r(session()->all());
	}
}
