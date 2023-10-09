<?php

namespace App\Http\Controllers;

use App\Mail\ChangeEmailAddress;
use App\Models\Country;
use App\Models\Location;
use App\Models\Settings;
use App\Models\Timezone;
use App\Models\User;
use App\Models\UserToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller {
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->middleware('auth')->except(['updateEmail']);
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		return view('user-suspended');
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
		$countries = Country::sortedList();

		if (Route::is('account.profile')) {
			$entries = 'resources/js/pages/user-profile.js';
			$timezones = Timezone::all()->sortBy('offset');

			return view('user-profile', compact(
				'entries',
				'countries',
				'timezones',
			));
		}

		$entries = 'resources/js/pages/user-address.js';
		$locations = Location::select('code')
			->where('code', 'like', '009%')
			->get()
			->sortBy('code')
			->toArray();

		return view('user-address', compact(
			'entries',
			'countries',
			'locations',
		));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request) {
		if (!$request->verify_password || !Hash::check($request->verify_password, Auth::user()->password)) {
			session()->flash("error", __('The provided password is not correct.'));
			return back();
		}
		
		$params = $request->all();

		$user = User::whereId(Auth::user()->id)->first();

		$is_profile = Route::is('account.profile.update');
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
			'user-phone_country_id.required' => app('ERRORS')['required'],
			'user-phone_country_id.numeric' => app('ERRORS')['numeric'],
			'user-phone_number.required' => app('ERRORS')['required'],
			'user-fax_country_id.required' => app('ERRORS')['required'],
			'user-fax_country_id.numeric' => app('ERRORS')['numeric'],
			'user-fax_number.required' => app('ERRORS')['required'],
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

		if ($is_profile) {
			session()->flash("success", __("Your profile has been updated."));
			return redirect()->route("account.profile");
		}

		session()->flash("success", __("Your address has been updated."));
		return redirect()->route("account.address");
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

	/**
	 * Send update email address confirmation email.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function updateEmailRequest(Request $request) {
		$params = $request->all();
		$email = $params['email'];

		$result = ['success' => false];

		$params_rules = [
			'email' => "required|email|unique:users,email",
			// 'email' => "required|email|unique:users,email," . Auth::user()->id,
		];

		$params_messages = [
			'email.required' => app('ERRORS')['required'],
			'email.email' => app('ERRORS')['email'],
			'email.unique' => app('ERRORS')['unique']['email'],
		];

		$validator = Validator::make($params, $params_rules, $params_messages);

		$result['success'] = !$validator->fails();

		if ($result['success']) {
			$provider = config('app.env') === 'production' ?
				config('project.mail.default_provider') :
				config('project.mail.default_dev_provider');
			$expiration = config('project.token_expiration_time');
			$token = Str::random(64);

			Log::channel('application')->info("[SENDING CHANGE_EMAIL EMAIL] {$email}");

			$res = UserToken::create(Auth::user()->id, $email, 'change-email', $token);

			if ($res['success']) {
				try {
					Mail::mailer($provider)
						->to($email)
						->send(new ChangeEmailAddress(route('account.email.update', ['token' => "{$token}?email={$email}"]), $expiration));

					$result['data'] = __('An email has been sent to :email. Click on the button in email to confirm the modification.', [
						'email' => "\"{$email}\"",
					]);

					Log::channel('application')->info("[EMAIL SENT]");
				} catch (\Throwable $th) {
					Log::channel('application')->info("[!!! ERROR !!!]");
					Log::channel('application')->info($th->__toString());

					$result['error'] = $th->getMessage();
				}
			} else {
				Log::channel('application')->info("[!!! ERROR !!!]");
				Log::channel('application')->info($res['error']);

				$result['error'] = $res['error'];
			}
		} else {
			$result['error'] = $validator->errors()->get('email')[0];
		}

		return response()->json($result);
	}

	/**
	 * Update user's email address.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function updateEmail(Request $request) {
		$token = $request->route()->parameter('token');
		// ^^^ why not "$token = $request->token" !!?? ^^^
		$email = $request->email;
		$connected = Auth::check();
		
		if ($token && $email) {
			$res = UserToken::verify($email, 'change-email', $token);

			if ($res['success']) {
				User::whereId($res['id'])->update(['email' => $email]);

				$message = __('Your email address has been updated.');

				if ($connected) {
					session()->flash('success', $message);
					return redirect()->route('account.profile');
				}

				session()->flash('success', $message);
				return redirect()->route('login');
			}
		}

		$responseCode = 503;
		$title = __('Error');
		$icon = 'fas fa-bomb';
		$message = __('An unexpected error has occurred.');

		if (isset($res['error'])) {
			switch ($res['error']) {
				case 'expired':
					$timezone = $connected ? Auth::user()->timezone : config('project.default_timezone');
					$time = Carbon::parse($res['time'])->setTimezone($timezone);

					$responseCode = 410;
					$title = __('Expired link');
					$icon = 'fas fa-hourglass';
					$message = __('The provided link is expired on :date at :time.', [
						'date' => $time->translatedFormat('l j F Y'),
						'time' => $time->translatedFormat('H:i:s'),
					]);
					break;
				case 'invalid':
				case 'format':
					$responseCode = 403;
					$title = __('Invalid link');
					$icon = 'fas fa-ban';
					$message = __('The provided link is not valid.');
					break;
				case 'not found':
					$responseCode = 404;
					$title = __('Page not found');
					$icon = 'far fa-circle-question';
					$message = __('Page not found') . ".";
					break;
			}
		}

		return response()->view('information', [
			'title' => $title,
			'icon' => $icon,
			'message' => $message,
		], $responseCode);
	}

	/**
	 * Send notification SMS.
	 *
	 * @param  String $name
	 * @param  Boolean $email
	 * @param  String $to
	 * @param  String $action
	 * @param  Array $event
	 * @param  Array $old_event
	 * @return Array $result
	 */
	private function sendSMS($country, $to, $lines) {
		$result = ['success' => false, 'data' => null];

		$to = preg_replace('/(\(0\)|\s)+/', '', $to);

		try {
			Log::channel('application')->info("[SENDING CHANGE_PHONE SMS] {$to}");

			$sms = new \App\Notifications\SmsMessage([
				'country' => $country,
				// 'provider' => "smsto",
			]);
			$sms = $sms->to($to);

			foreach ($lines as $line) {
				$sms = $sms->line($line);
			}

			$res = ['success' => false, 'data' => null];

			if (config('app.env') === 'production' || config('project.send_sms')) {
				$res = $sms->send();
			} else {
				$res = $sms->dryRun()->send();
			}

			if ($res['success']) {
				Log::channel('application')->info("[SMS SENT]");

				$result['success'] = true;
				$result['data'] = $res;
			} else {
				Log::channel('application')->info("[!!! SMS ERROR !!!]");
				Log::channel('application')->info(print_r($res, true));

				$result['error'] = $res['data'];
			}
		} catch (\Throwable $th) {
			Log::channel('application')->info("[!!! ERROR !!!]");
			Log::channel('application')->info($th->__toString());

			$result['error'] = $th->getMessage();
		}

		return $result;
	}

	/**
	 * Generate update phone array.
	 *
	 * @param  String $country_id
	 * @param  String $number
	 * @param  String $code
	 * @return Array
	 */
	public function generateUpdatePhoneData(string $country_id, string $number) {
		return [
			'country_id' => $country_id,
			'number' => $number,
		];
	}

	/**
	 * Send update phone number confirmation SMS.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function updatePhoneRequest(Request $request) {
		$result = ['success' => false];

		$params = $request->all();

		$params_rules = [
			'phone_country_id' => "required|numeric",
			'phone_number' => "required",
		];

		$params_messages = [
			'phone_country_id.required' => app('ERRORS')['required'],
			'phone_country_id.numeric' => app('ERRORS')['numeric'],
			'phone_number.required' => app('ERRORS')['required'],
		];

		$validator = Validator::make($params, $params_rules, $params_messages);

		$result['success'] = !$validator->fails();

		if ($result['success']) {
			$expiration = config('project.token_expiration_time');
			$token = mt_rand(123456, 987654);
			$phone = $this->generateUpdatePhoneData(
				$params['phone_country_id'],
				$params['phone_number'],
			);
			$data = json_encode($phone);

			Log::channel('application')->info("[SENDING CHANGE_PHONE PHONE] {$data}");

			$res = UserToken::create(Auth::user()->id, $data, 'change-phone', $token);

			if ($res['success']) {
				$country = Country::select(['code', 'prefix'])
					->whereId($phone['country_id'])
					->first();
				$number = $country->prefix . " " . $phone['number'];


				$sms_res = $this->sendSMS($country->code, $number, [
					__('Your :app verification code is: :code', [
						'app' => config('project.app_name'),
						'code' => $token,
					]),
					'',
					__('This code will expire in :count minutes.', ['count' => $expiration])
				]);

				if ($sms_res['success']) {
					$result['code'] = config('app.env') === 'production' ? '' : $token;
					$result['success'] = true;
				} else {
					UserToken::deleteRow(Auth::user()->id, $data, 'change-phone');
					$result['error'] = $res['error'];
				}
			} else {
				Log::channel('application')->info("[!!! ERROR !!!]");
				Log::channel('application')->info($res['error']);

				$result['error'] = $res['error'];
			}
		} else {
			$result['error'] = $validator->errors()->get('phone_number')[0];
		}

		return response()->json($result);
	}

	/**
	 * Update user's phone number.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function updatePhone(Request $request) {
		$result = ['success' => false];
		
		$params = $request->all();
		
		if ($params['phone_country_id'] && $params['phone_number']) {
			$phone = $this->generateUpdatePhoneData(
				$params['phone_country_id'],
				$params['phone_number'],
			);

			$res = UserToken::verify(json_encode($phone), 'change-phone', $params['token']);

			if ($res['success']) {
				User::whereId($res['id'])->update([
					'phone_country_id' => $phone['country_id'],
					'phone_number' => $phone['number'],
				]);

				$prefix = Country::whereId($phone['country_id'])->first()->prefix;
				
				$result['data'] = "{$prefix} {$phone['number']}";
				$result['message'] = __('Your phone number has been updated.');
				$result['success'] = true;
			} else {
				$result['error'] = __('An unexpected error has occurred.');

				if (isset($res['error'])) {
					switch ($res['error']) {
						case 'expired':
							$time = Carbon::parse($res['time'])->setTimezone(Auth::user()->timezone);
							$result['error'] = __('The provided code is expired on :date at :time.', [
								'date' => $time->translatedFormat('l j F Y'),
								'time' => $time->translatedFormat('H:i:s'),
							]);
							break;
						case 'invalid':
						case 'format':
						case 'not found':
							$result['error'] = __('The provided code is not valid.');
							break;
					}
				}
			}
		} else {
			$result['error'] = __('Incomplete request!');
		}

		return response()->json($result);
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
