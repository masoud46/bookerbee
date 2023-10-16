<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Settings;
use App\Models\Type;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

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
		$cal_slots = [10, 15, 30, 45, 60];

		$locations = Location::fetchAll();
		$types = Type::all()->sortBy('code');
		$settings = Settings::whereUserId(Auth::user()->id)->first();

		$settings->amount = currency_format($settings->amount);
		
		$msg_email = $settings->msg_email ? json_decode($settings->msg_email, true) : [];
		$msg_sms = $settings->msg_sms ? json_decode($settings->msg_sms, true) : [];
		$settings->msg_email_checked = count($msg_email) > 0;
		$settings->msg_sms_checked = count($msg_sms) > 0;
		
		// Set message to null for absent (or recently added) locales
		$locales = array_keys(LaravelLocalization::getSupportedLocales());
		foreach ($locales as $locale) {
			if (!isset($msg_email[$locale])) $msg_email[$locale] = null;
			if (!isset($msg_sms[$locale])) $msg_sms[$locale] = null;
		}

		$settings->msg_email = $msg_email;
		$settings->msg_sms = $msg_sms;

		return view('settings', compact(
			'entries',
			'locations',
			'types',
			'cal_slots',
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
		$locales = array_keys(LaravelLocalization::getSupportedLocales());
		$msg_email_checked = null;
		$msg_sms_checked = null;
		$settings_object = [];

		if (!isset($params['settings-msg_email_checked'])) {
			foreach ($locales as $locale) $params["settings-msg_email-{$locale}"] = null;
		} else {
			$msg_email_checked = true;
		}

		if (!isset($params['settings-msg_sms_checked'])) {
			foreach ($locales as $locale) $params["settings-msg_sms-{$locale}"] = null;
		} else {
			$msg_sms_checked = true;
		}

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
			'settings-msg_email' => app('ERRORS')['one_required'],
			'settings-msg_sms' => app('ERRORS')['one_required'],
		];

		// check location against secondary address
		$user = Auth::user();
		if (!$user->address2_line1 || !$user->address2_code || !$user->address2_city || !$user->address2_country_id) {
			$locations = Location::fetchAll();
			$locations = array_column($locations->toArray(), 'id', 'code');
			if (intval($settings_object['location']) === $locations['009b']) {
				$params_rules['settings-location'] = "required|numeric|not_in:{$locations['009b']}";
			}
		}

		// set email personal message params
		if ($msg_email_checked) {
			$params['settings-msg_email'] = null;
			foreach ($locales as $locale) {
				if ($params["settings-msg_email-{$locale}"]) {
					$params['settings-msg_email'] = true;
					break;
				}
			}
			$params_rules['settings-msg_email'] = "required";
		}
		// set sms personal message params
		if ($msg_sms_checked) {
			$params['settings-msg_sms'] = null;
			foreach ($locales as $locale) {
				if ($params["settings-msg_sms-{$locale}"]) {
					$params['settings-msg_sms'] = true;
					break;
				}
			}
			$params_rules['settings-msg_sms'] = "required";
		}

		$validator = Validator::make($params, $params_rules, $params_messages);

		if ($validator->fails()) {
			session()->flash("error", app('ERRORS')['form']);
			return back()->withErrors($validator->errors())->withInput();
		}

		$msg_email = [];
		$msg_sms = [];
		foreach ($locales as $locale) {
			if ($settings_object["msg_email-{$locale}"]) {
				$msg_email[$locale] = $settings_object["msg_email-{$locale}"];
			}
			unset($settings_object["msg_email-{$locale}"]);

			if ($settings_object["msg_sms-{$locale}"]) {
				$msg_sms[$locale] = $settings_object["msg_sms-{$locale}"];
			}
			unset($settings_object["msg_sms-{$locale}"]);
		}
		$settings_object['msg_email'] = count($msg_email) ?json_encode($msg_email, JSON_UNESCAPED_UNICODE) : null;
		$settings_object['msg_sms'] = count($msg_sms) ?json_encode($msg_sms, JSON_UNESCAPED_UNICODE) : null;

		$settings_object['amount'] = intval(currency_parse($settings_object['amount']));
		$settings = Settings::whereUserId($user->id)->first();
		foreach ($settings_object as $key => $value) {
			if (substr($key, -8) !== '_checked') {
				$settings[$key] = $value;
			}
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
