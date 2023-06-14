<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Settings;
use App\Models\Type;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller {
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
		$entries = 'resources/js/pages/settings.js';

		$locations = Location::all()->sortBy('code');
		$types = Type::all()->sortBy('code');
		$settings = Settings::whereUserId(Auth::user()->id)->first();

		$settings->amount = currency_format($settings->amount);

		return view('settings', compact(
			'entries',
			'locations',
			'types',
			'settings',
		));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Models\User  $user
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request) {
		$params = $request->all();
		$settings_object = [];

		foreach ($params as $key => $value) {
			$field = explode("-", $key, 2);
			switch ($field[0]) {
				case "settings":
					$settings_object[$field[1]] = $value;
					break;
			}
		}

		$settings_object['type_change_alert'] = $request->has('settings-type_change_alert');
		$currency_regex = currency_regex();

		$params_rules = [
			'settings-amount' => "required|regex:{$currency_regex}",
			'settings-location' => "required",
			'settings-cal_min_time' => "required",
			'settings-cal_max_time' => "required",
			'settings-cal_slot' => "required",
		];

		$params_messages = [
			'settings-amount.required' => app('ERRORS')['required'],
			'settings-amount.regex' => app('ERRORS')['regex']['price'],
			'settings-location.required' => app('ERRORS')['required'],
			'settings-location.not_in' => app('ERRORS')['regex']['location'],
			'settings-cal_min_time.required' => app('ERRORS')['required'],
			'settings-cal_max_time.required' => app('ERRORS')['required'],
			'settings-cal_slot.required' => app('ERRORS')['required'],
		];

		// check location against secondary address
		$user = Auth::user();
		if (!$user->address2_line1 || !$user->address2_code || !$user->address2_city || !$user->address2_country_id) {
			$locations = Location::all()->sortBy('code');
			$locations = array_column($locations->toArray(), 'id', 'code');
			if (intval($settings_object['location']) === $locations['009b']) {
				$params_rules['settings-location'] = "required|numeric|not_in:{$locations['009b']}";
			}
		}

		$validator = Validator::make($params, $params_rules, $params_messages);

		if ($validator->fails()) {
			session()->flash("error", app('ERRORS')['form']);
			return back()->withErrors($validator->errors())->withInput();
		}

		$settings_object['amount'] = intval(currency_parse($settings_object['amount']));
		$settings = Settings::whereUserId($user->id)->first();
		foreach ($settings_object as $key => $value) {
			$settings[$key] = $value;
		}
		$settings->save();

		session()->flash("success", __("Settings have been updated."));
		return redirect()->route("settings");
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

}
