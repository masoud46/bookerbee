<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentEmail;
use App\Models\Country;
use App\Models\Event;
use App\Models\Location;
use App\Models\Settings;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Masoud46\LaravelApiMail\Facades\ApiMail;
use Masoud46\LaravelApiSms\Facades\ApiSms;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Vinkla\Hashids\Facades\Hashids;

class EventController extends Controller {
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->middleware('auth')->except(['export']);
	}

	/**
	 * Get a listing of the resource between two dates.
	 *
	 * @return String
	 */
	private function getUserPhone() {
		$prefix = Country::select("prefix")
			->whereId(Auth::user()->phone_country_id)
			->first()
			->prefix;

		return $prefix . " " . Auth::user()->phone_number;
	}

	/**
	 * Set event's classNames.
	 *
	 * @return String
	 */
	private function eventClassNames($location_id, $all_day = false, $category = null) {
		$classNames = [];

		if ($all_day) {
			$classNames[] = 'fc-allday-event';
		}

		if ($category === 2) {
			$classNames[] = 'fc-private-event';
			return $classNames;
		}

		if (!$location_id) {
			$classNames[] = 'fc-locked-event';
			return $classNames;
		}

		$locations = array_column(
			Location::fetchAll()->toArray(),
			"code",
			"id"
		);

		if (!in_array($locations[$location_id], ["009", "009b"])) {
			return 'fc-out-of-office-event';
		}

		return null;
	}

	/**
	 * Convert database row to FullCalendar JavaScript compatible object.
	 *
	 * @param  Event $event
	 * @return Array
	 */
	private function dbToJs(Event $e) {
		$event = [
			'id' => $e->id,
			'allDay' => $e->all_day === 1,
			'classNames' => $this->eventClassNames($e->location_id, $e->all_day === 1, $e->category),
			'extendedProps' => [
				'category' => $e->category,
			],
		];
		$rrule = $e->rrule_freq ? [
			'freq' => $e->rrule_freq,
			'dtstart' => $e->all_day ? substr($e->rrule_dtstart, 0, 10) : $e->rrule_dtstart,
		] : null;

		if ($e->patient_id) {
			$event['title'] = $e->patient_name;
			$event['extendedProps']['patient'] = [
				'id' => $e->patient_id,
				'name' => $e->patient_name,
				'locale' => $e->patient_locale,
			];

			if ($e->patient_email) {
				$event['extendedProps']['patient']['email'] = $e->patient_email;
			}

			if ($e->patient_phone_number) {
				$event['extendedProps']['patient']['phone'] = $e->patient_phone_number;
				$event['extendedProps']['patient']['phoneCountryId'] = $e->patient_phone_country_id;
				$event['extendedProps']['patient']['phoneCountryCode'] = $e->patient_phone_country_code;
			}

			// if ($e->location_id) {
			// 	$event['extendedProps']['location_id'] = $e->location_id;
			// }
			$event['extendedProps']['location_id'] = $e->location_id ?? null;

			if ($e->location_name) {
				$event['extendedProps']['location'] = [
					'name' => $e->location_name,
					'address' => $e->location_address,
					'code' => $e->location_code,
					'city' => $e->location_city,
					'country_id' => $e->location_country_id,
				];
			}
		}

		if ($rrule || $e->all_day) {
			$event['display'] = 'background';

			if ($rrule) {
				if ($e->rrule_until) $rrule['until'] = $e->rrule_until;
				if ($e->rrule_byweekday) $rrule['byweekday'] = explode(',', $e->rrule_byweekday);

				$event['rrule'] = $rrule;
				$event['editable'] = false;
				$event['startEditable'] = false;
			}
		}

		if ($e->title) $event['title'] = $e->title;
		// if ($e->start) $event['start'] = $e->all_day ? substr($e->start, 0, 10) : $e->start;
		// if ($e->end) $event['end'] = $e->all_day ? substr($e->end, 0, 10) : $e->end;
		if ($e->start) $event['start'] = $e->start;
		if ($e->end) $event['end'] = $e->end;
		if ($e->duration) $event['duration'] = $e->duration;

		return $event;
	}
	/**
	 * Get a listing of the resource between two dates.
	 *
	 * @param  String $from
	 * @param  String $to
	 * @return Array
	 */
	private function get($start = null, $end = null) {
		$start = Carbon::parse($start)->subDays(7);
		$end = Carbon::parse($end)->addDays(7);

		$prefixes = array_column(
			Country::select(["id", "code", "prefix"])->get()->toArray(),
			null,
			"id"
		);
		$db_events = Event::select([
			"events.id",
			"events.patient_id",
			"events.location_id",
			"events.category",
			"events.title",
			"events.all_day",
			DB::raw('DATE_FORMAT(events.start, "%Y-%m-%dT%TZ") AS start'),
			DB::raw('DATE_FORMAT(events.end, "%Y-%m-%dT%TZ") AS end'),
			"events.duration",
			"events.rrule_freq",
			"rrule_dtstart",
			"events.rrule_until",
			"events.rrule_byweekday",
			"events.status",
			DB::raw('CONCAT(patients.lastname, ", ", patients.firstname) AS patient_name'),
			"patients.email AS patient_email",
			"patients.phone_number AS patient_phone_number",
			"patients.phone_country_id AS patient_phone_country_id",
			"patients.locale AS patient_locale",
			"event_locations.name AS location_name",
			"event_locations.address AS location_address",
			"event_locations.code AS location_code",
			"event_locations.city AS location_city",
			"event_locations.country_id AS location_country_id",
		])
			->where("events.user_id", "=", Auth::user()->id)
			->where("events.status", "=", true)
			->where(function ($query) use ($start, $end) {
				if (in_array('agenda_lock', Auth::user()->features)) {
					$query
						->whereNotNull("events.rrule_freq")
						->orWhere(function ($query2) use ($start, $end) {
							$query2
								->where("events.start", ">=", $start)
								->where("events.end", "<=", $end);
						});
				} else {
					$query
						->where("events.category", "<>", 0)
						->where("events.start", ">=", $start)
						->where("events.end", "<=", $end);
				}
			})
			->leftJoin("patients", "patients.id", "=", "events.patient_id")
			->leftJoin("event_locations", "event_locations.event_id", "=", "events.id")
			->get();

		// dd($db_events->toArray());
		$events = [];
		foreach ($db_events as $e) {
			if ($e->patient_phone_number) {
				$phone = ltrim($e->patient_phone_number, "0");
				$e->patient_phone_number = "{$prefixes[$e->patient_phone_country_id]['prefix']} {$phone}";
				$e->patient_phone_country_id = $prefixes[$e->patient_phone_country_id]['id'];
				$e->patient_phone_country_code = $prefixes[$e->patient_phone_country_id]['code'];
			}

			$event = $this->dbToJs($e);

			$events[] = $event;
		}

		return $events;
	}


	/**
	 * Get the extra information. address according to events's location id and personal
	 * 1. Address, from events's location id.
	 * 2. Personal messages to add into email and sms, from settings.
	 *
	 * @param  Array $params
	 * @return Array
	 */
	private function getExtraInfo($params) {
		$data = User::select([
			"users.address_line1",
			"users.address_line2",
			"users.address_line3",
			"users.address_code",
			"users.address_city",
			"users.address2_line1",
			"users.address2_line2",
			"users.address2_line3",
			"users.address2_code",
			"users.address2_city",
			"countries.name AS address_country",
			"countries2.name AS address2_country",
			"settings.duration",
			"settings.msg_email",
			"settings.msg_sms",
		])
			->join("settings", "settings.id", "=", "users.id")
			->join("countries", "countries.id", "=", "users.address_country_id")
			->leftJoin("countries AS countries2", "countries2.id", "=", "users.address2_country_id")
			->where("users.id", "=", $params['user_id'])
			->first();

		$location = Location::whereId($params['location_id'])->first()->code;
		$msg_email = $data->msg_email ? json_decode($data->msg_email, true) : [];
		$msg_sms = $data->msg_sms ? json_decode($data->msg_sms, true) : [];
		$data->address_country = __($data->address_country);

		if ($data->address2_country) {
			$data->address2_country = __($data->address2_country);
		}

		$address = [];
		switch ($location) {
			case '003':
				$address = ['line1' => __('Your residence')];
				break;
			case '009':
				$address = [
					"line1" => $data->address_line1,
					"line2" => $data->address_line2,
					"line3" => $data->address_line3,
					"code" => $data->address_code,
					"city" => $data->address_city,
					"country" => $data->address_country,
				];
				break;
			case '009b':
				$address = [
					"line1" => $data->address2_line1,
					"line2" => $data->address2_line2,
					"line3" => $data->address2_line3,
					"code" => $data->address2_code,
					"city" => $data->address2_city,
					"country" => $data->address2_country,
				];
				break;
		}

		return [
			'duration' => $data->duration,
			'msg_email' => $msg_email,
			'msg_sms' => $msg_sms,
			'address' => $address,
		];
	}

	/**
	 * Add or update or delete event's locations.
	 *
	 * @param  Int $event_id
	 * @param  Array $event_location
	 * @return Array $result
	 */
	private function addUpdateDeleteLocation($event_id, $event_location) {
		$result = ['success' => false];

		try {
			if ($event_location) {
				$location = array_merge($event_location, [
					'event_id' => $event_id,
				]);

				if (DB::table('event_locations')->whereEventId($event_id)->first()) {
					DB::table('event_locations')->whereEventId($event_id)->update($location);
				} else {
					DB::table('event_locations')->insert($location);
				}
			} else {
				DB::table('event_locations')->whereEventId($event_id)->delete();
			}

			$result['success'] = true;
		} catch (\Throwable $th) {
			$result['error'] = $th->__toString();
		}

		return $result;
	}

	/**
	 * Send notification email.
	 *
	 * @param  String $action
	 * @param  Array $event
	 * @param  Array $old_event
	 * @return Array $result
	 */
	private function sendEmail($action, $event, $old_event = null) {
		if (strlen($action) > config('project.event_action_max_length')) {
			return ['success' => false, 'error' => "action string length is greater " . config('project.event_action_max_length')];
		}

		if (config('app.env') !== 'production' && !config('project.send_emails')) {
			return ['success' => true];
		}

		$provider = config('app.env') === 'production'
			? config('api-mail.default_provider')
			: config('api-mail.default_dev_provider');

		$to = $event['extendedProps']['patient']['email'];
		$locale = $event['extendedProps']['patient']['locale'];

		$info = $this->getExtraInfo([
			'user_id' => Auth::user()->id,
			'location_id' => $event['extendedProps']['location_id'],
		]);
		$event['duration'] = $info['duration'];
		$event['address'] = $event['extendedProps']['address'] ?? $info['address'];

		$personal_message = $info['msg_email'][$locale] ?? array_shift($info['msg_email']) ?? null;

		if ($personal_message && $action !== "delete") {
			$event['msg_email'] = $personal_message;
		}

		$result = ['success' => false];

		Log::channel('agenda')->info("[SENDING EMAIL ({$provider})] {$to}");

		$body = (new AppointmentEmail($action, $event, $old_event))->render();
		$subject = __("New appointment");

		if ($action === "update") {
			if ($old_event) {
				$subject = __("Your appointment has been rescheduled");
			} else {
				$subject = __("The location of you appointment has been changed");
			}
		} else if ($action === "delete") {
			$subject = __("Your appointment has been canceled");
		}

		$payload = [
			'to' => $to,
			'subject' => $subject,
			'body' => $body,
		];

		try {
			$mail = ApiMail::provider($provider);
			$res = $mail->send($payload);

			if (!$res->success) { // Retry after 2 seconds
				Log::channel('agenda')->info("[!!! EMAIL ERROR !!!]");
				Log::channel('agenda')->info($res->message);
				Log::channel('agenda')->info("[RESENDING EMAIL]");

				sleep(2);
				$res = $mail->send($payload);

				if (!$res->success) {
					Log::channel('agenda')->info("[!!! EMAIL ERROR !!!]");
					Log::channel('agenda')->info($res->message);

					$result['error'] = $res->message;
				}
			}

			if ($res->success) {
				$result['success'] = true;

				Log::channel('agenda')->info("[EMAIL SENT]");
			}
		} catch (\Exception $e) {
			Log::channel('agenda')->info("[!!! ERROR !!!]");
			Log::channel('agenda')->info($e->getMessage());

			$result['error'] = $e->getMessage();
		}

		return $result;
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
	private function sendSMS($action, $event, $old_event = null) {
		if (strlen($action) > config('project.event_action_max_length')) {
			return ['success' => false, 'error' => "action string length is greater " . config('project.event_action_max_length')];
		}

		$result = ['success' => false, 'data' => null];

		$provider = config('app.env') === 'production'
			? config('api-sms.default_provider')
			: config('api-sms.default_dev_provider');

		$user_name = ucfirst(Auth::user()->firstname) . " " . strtoupper(Auth::user()->lastname);
		$name = ucfirst(trim(explode(",", $event['extendedProps']['patient']['name'])[1]));
		$country_id = $event['extendedProps']['patient']['phoneCountryId'];
		$country_code = $event['extendedProps']['patient']['phoneCountryCode'];
		$to = preg_replace('/(\(0\)|\s)+/', '', $event['extendedProps']['patient']['phone']);
		$locale = $event['extendedProps']['patient']['locale'];

		$info = $this->getExtraInfo([
			'user_id' => Auth::user()->id,
			'location_id' => $event['extendedProps']['location_id'],
		]);

		$message = __("Hello :name", ['name' => $name]) . ",\n";
		$personal_message = $info['msg_sms'][$locale] ?? array_shift($info['msg_sms']) ?? null;

		switch ($action) {
			case 'add':
				$message .= __("Your appointment of :date at :start with :name has been confirmed.", [
					'name' => $user_name,
					'date' => Carbon::parse($event['localStart'])->format('d/m/Y'),
					'start' => Carbon::parse($event['localStart'])->format('H:i'),
				]);
				break;
			case 'update':
				$message .= $old_event ?
					__("Your appointment of :old_date at :old_start with :name has been rescheduled for :date at :start.", [
						'name' => $user_name,
						'old_date' => Carbon::parse($old_event['localStart'])->format('d/m/Y'),
						'old_start' => Carbon::parse($old_event['localStart'])->format('H:i'),
						'date' => Carbon::parse($event['localStart'])->format('d/m/Y'),
						'start' => Carbon::parse($event['localStart'])->format('H:i'),
					]) :
					__("The location for you appointment of :date at :start with :name has been changed.", [
						'name' => $user_name,
						'date' => Carbon::parse($event['localStart'])->format('d/m/Y'),
						'start' => Carbon::parse($event['localStart'])->format('H:i'),
					]);
				break;
			case 'delete':
				$personal_message = null;
				$message .= __("Your appointment of :date at :start with :name has been canceled.", [
					'name' => $user_name,
					'date' => Carbon::parse($event['localStart'])->format('d/m/Y'),
					'start' => Carbon::parse($event['localStart'])->format('H:i'),
				]);
				break;
		}

		Log::channel('agenda')->info("[SENDING SMS ({$provider})] {$to}");

		if ($action !== 'delete') {
			// if (isset($event['extendedProps']['patient']['email'])) {
			// 	$message .= " " . __("A detailed email has been sent to you.");
			// }
			$address = $event['extendedProps']['address'] ?? $info['address'];
			$message .= "\n" . ($action === 'update' && !$old_event
				? __('New address:')
				: __('Address:')
			) . ' ' . makeOneLineAddress($address);
		}

		if ($personal_message) {
			$message .= "\n\n" . $personal_message;
		}

		$res = ['success' => false, 'data' => null];
		$payload = [
			'country' => $country_code,
			'to' => $to,
			'message' => $message,
			'dryrun' => config('app.env') !== 'production' && !config('project.send_sms'),
		];

		try {
			$sms = ApiSms::provider($provider);
			$res = $sms->send($payload);

			if (!$res->success) { // Retry after 2 seconds
				Log::channel('agenda')->info("[!!! SMS ERROR !!!]");
				Log::channel('agenda')->info($res->message);
				Log::channel('agenda')->info("[RESENDING SMS]");

				sleep(2);
				$res = $sms->send($payload);

				if (!$res->success) {
					Log::channel('agenda')->info("[!!! SMS ERROR !!!]");
					Log::channel('agenda')->info($res->message);

					$result['error'] = $res->message;
				}
			}

			if ($res->success) {
				$result['success'] = true;
				$result['data'] = $res;

				Log::channel('agenda')->info("[SMS SENT]");
			}
		} catch (\Exception $e) {
			Log::channel('agenda')->info("[!!! ERROR !!!]");
			Log::channel('agenda')->info($e->getMessage());

			$result['error'] = $e->getMessage();
		}

		return $result;
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		if (!in_array('agenda', Auth::user()->features)) {
			abort(404);
		}

		$entries = 'resources/js/pages/agenda.js';
		$settings = Settings::whereUserId(Auth::user()->id)->first()->toArray();
		$countries = Country::select(["id", "code", "name", "prefix"])->get()->toArray();
		$locations = Location::fetchAll();

		return view('agenda', compact('entries', 'settings', 'countries', 'locations'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {
		if ( // Reject the lock request if "agenda_lock" is not in user's features
			!isset($request->event['extendedProps']['patient']['id']) &&
			!in_array('agenda_lock', Auth::user()->features)
		) {
			return response()->json(['success' => false]);
		}

		$data = $request->all()['event'];
		$location = $data['extendedProps']['location'] ?? null;

		DB::beginTransaction();

		$event = new Event();
		$event->user_id = Auth::user()->id;
		$event->all_day = $data['allDay'];
		$event->title = isset($data['extendedProps']['patient']) ? null : ($data['title'] ?? null);

		if (isset($data['extendedProps']['patient']['id'])) {
			$event->patient_id = $data['extendedProps']['patient']['id'];
			$event->location_id = $data['extendedProps']['location_id'];
			$event->category = 1;
		} else {
			$event->location_id = null;
			$event->category = 0;
		}

		if (isset($data['duration'])) {
			$event->duration = $data['duration'];
		}

		if (isset($data['rrule'])) {
			$event->rrule_dtstart = $data['rrule']['dtstart'];

			if (isset($data['rrule']['until'])) {
				$event->rrule_until = $data['rrule']['until'];
			}

			if (isset($data['rrule']['freq'])) {
				$event->rrule_freq = $data['rrule']['freq'];
			}

			if (isset($data['rrule']['byweekday'])) {
				$event->rrule_byweekday = implode(',', $data['rrule']['byweekday']);
			}
		} else {
			$event->start = Carbon::parse($data['start'])->format('Y-m-d H:i:s');
			$event->end = Carbon::parse($data['end'])->format('Y-m-d H:i:s');
		}

		if ($event->category === 1) {
			$event->setReminders($data['extendedProps']['patient']);
		}

		$event->save();

		// Add location (006) to database
		$res = $this->addUpdateDeleteLocation($event->id, $location);
		if (!$res['success']) {
			DB::rollBack();
			Log::debug($res['error']);

			$result['error'] = $res['error'];

			return response()->json($result);
		}

		$email = $data['extendedProps']['patient']['email'] ?? null;
		$phone = $data['extendedProps']['patient']['phone'] ?? null;
		$sms = $phone && in_array("sms", Auth::user()->features);
		$classNames = $this->eventClassNames($event->location_id, $event->all_day);
		$result = [
			'success' => false,
			'id' => $event->id,
			'category' => $event->category,
			'location_id' => $event->location_id,
		];

		if ($classNames) {
			$result['classNames'] = $classNames;
		}

		if (isset($data['rrule']) || $event->all_day) {
			$result['display'] = 'background';
		}

		if ($event->patient_id && ($email || $sms)) {
			Log::channel('agenda')->info(
				"Event ADD: {$event->id} - email " .
					($email ? "YES" : "NO") . " - phone " .
					($phone ? "YES" : "NO") . " - sms " .
					($sms ? "YES" : "NO")
			);

			LaravelLocalization::setLocale($data['extendedProps']['patient']['locale']);

			if (isset($data['extendedProps']['location'])) {
				$data['extendedProps']['address'] = locationToAddress($data['extendedProps']['location']);
			}

			$sms_result = ['success' => false];
			$email_result = ['success' => false];

			if ($sms) {
				$sms_result = $this->sendSMS("add", $data);
			}

			if ($email) {
				$data['hash_id'] = Hashids::encode($event->id);
				$data['user_phone'] = $this->getUserPhone();

				$email_result = $this->sendEmail("add", $data);
			}

			// Successful if either SMS or email has been sent
			$result['success'] = $sms_result['success'] || $email_result['success'];

			if ($result['success']) {
				DB::commit();
			} else {
				DB::rollBack();

				$result['email_error'] = $email_result['error'] ?? null;
				$result['sms_error'] = $sms_result['error'] ?? null;
			}

			Log::channel('agenda')->info("------------------------------------------------------------");
		} else {
			DB::commit();

			$result['success'] = true;
		}

		return response()->json($result);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Models\Event $event
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, Event $event) {
		$data = $request->all();
		$old_event = $data['oldEvent'];
		$data = $data['event'];
		$location = $data['extendedProps']['location'] ?? null;

		$result = ['success' => false];

		DB::beginTransaction();

		$event->all_day = $data['allDay'];
		$event->title = $event->patient_id ? null : ($data['title'] ?? null);
		$event->start = Carbon::parse($data['start'])->format('Y-m-d H:i:s');
		$event->end = Carbon::parse($data['end'])->format('Y-m-d H:i:s');

		if (isset($data['extendedProps']['patient']['id'])) {
			$event->location_id = $data['extendedProps']['location_id'];
		} else {
			$event->location_id = null;
		}

		if ($event->category === 1) {
			$event->setReminders($data['extendedProps']['patient']);
		}

		// Update/delete location (006) to/from database
		$res = $this->addUpdateDeleteLocation($event->id, $location);
		if (!$res['success']) {
			DB::rollBack();
			Log::debug($res['error']);

			$result['error'] = $res['error'];

			return response()->json($result);
		}

		$event->save();

		$email = $data['extendedProps']['patient']['email'] ?? null;
		$phone = $data['extendedProps']['patient']['phone'] ?? null;
		$sms = $phone && in_array("sms", Auth::user()->features);
		$classNames = $this->eventClassNames($event->location_id, $event->all_day);

		if ($classNames) {
			$result['classNames'] = $classNames;
		}

		if ($event->patient_id && ($email || $sms)) {
			Log::channel('agenda')->info(
				"Event UPDATE: {$event->id} - email " .
					($email ? "YES" : "NO") . " - phone " .
					($phone ? "YES" : "NO") . " - sms " .
					($sms ? "YES" : "NO")
			);

			LaravelLocalization::setLocale($data['extendedProps']['patient']['locale']);

			if (isset($data['extendedProps']['location'])) {
				$data['extendedProps']['address'] = locationToAddress($data['extendedProps']['location']);
			}

			$sms_result = ['success' => false];
			$email_result = ['success' => false];

			if ($sms) {
				$sms_result = $this->sendSMS("update", $data, $old_event);
			}

			if ($email) {
				$data['hash_id'] = Hashids::encode($data['id']);
				$data['user_phone'] = $this->getUserPhone();

				$email_result = $this->sendEmail("update", $data, $old_event);
			}

			// Successful if either SMS or email has been sent
			$result['success'] = $sms_result['success'] || $email_result['success'];

			if ($result['success']) {
				DB::commit();
			} else {
				DB::rollBack();

				$result['email_error'] = $email_result['error'] ?? null;
				$result['sms_error'] = $sms_result['error'] ?? null;
			}

			Log::channel('agenda')->info("------------------------------------------------------------");
		} else {
			DB::commit();

			$result['success'] = true;
		}

		return response()->json($result);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Models\Event $event
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Request $request, Event $event) {
		$data = $request->all()['event'];

		DB::beginTransaction();

		$event->status = false;
		$event->save();

		// Delete location (006) from database
		$res = $this->addUpdateDeleteLocation($event->id, null);
		if (!$res['success']) {
			DB::rollBack();
			Log::debug($res['error']);

			$result['error'] = $res['error'];

			return response()->json($result);
		}

		$email = $data['extendedProps']['patient']['email'] ?? null;
		$phone = $data['extendedProps']['patient']['phone'] ?? null;
		$sms = $phone && in_array("sms", Auth::user()->features);
		$result = ['success' => false, 'id' => $event->id];

		if ($event->patient_id && ($email || $sms)) {
			Log::channel('agenda')->info(
				"Event DELETE: {$event->id} - email " .
					($email ? "YES" : "NO") . " - phone " .
					($phone ? "YES" : "NO") . " - sms " .
					($sms ? "YES" : "NO")
			);

			LaravelLocalization::setLocale($data['extendedProps']['patient']['locale']);

			$sms_result = ['success' => false];
			$email_result = ['success' => false];

			if ($sms) {
				$sms_result = $this->sendSMS("delete", $data);
			}

			if ($email) {
				$email_result = $this->sendEmail("delete", $data);
			}

			// Successful if either SMS or email has been sent
			$result['success'] = $sms_result['success'] || $email_result['success'];

			if ($result['success']) {
				DB::commit();
			} else {
				DB::rollBack();

				$result['email_error'] = $email_result['error'] ?? null;
				$result['sms_error'] = $sms_result['error'] ?? null;
			}

			Log::channel('agenda')->info("------------------------------------------------------------");
		} else {
			DB::commit();

			$result['success'] = true;
		}

		return response()->json($result);
	}

	/**
	 * Fetch resources between two dates.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function fetch(Request $request) {
		$events = $this->get($request->start, $request->end);

		return response()->json($events);
	}

	/**
	 * Generate iCalendar content for event.
	 *
	 * @param  Array $event
	 * @return String
	 */
	private function iCalendar($event) {
		$ical_template =
			"BEGIN:VCALENDAR" . PHP_EOL .
			"VERSION:2.0" . PHP_EOL .
			"CALSCALE:GREGORIAN" . PHP_EOL .
			"METHOD:PUBLISH" . PHP_EOL .
			"%sEND:VCALENDAR";

		$ical_body =
			"BEGIN:VEVENT" . PHP_EOL .
			"DTSTART:%s" . PHP_EOL .
			"DTEND:%s" . PHP_EOL .
			"ORGANIZER;CN=%s:mailto:%s" . PHP_EOL .
			"DESCRIPTION:%s" . PHP_EOL .
			"SEQUENCE:0" . PHP_EOL .
			"STATUS:CONFIRMED" . PHP_EOL .
			"LOCATION:%s" . PHP_EOL .
			"SUMMARY:%s" . PHP_EOL .
			"CREATED:%s" . PHP_EOL .
			"DTSTAMP:%s" . PHP_EOL .
			"TRANSP:OPAQUE" . PHP_EOL .
			"PRIORITY:1" . PHP_EOL .
			"BEGIN:VALARM" . PHP_EOL .
			// "TRIGGER;VALUE=DATE-TIME:%s" . PHP_EOL .
			"TRIGGER:-PT1D" . PHP_EOL .
			"ACTION:DISPLAY" . PHP_EOL .
			"DESCRIPTION:" . __('Appointment reminder') . PHP_EOL .
			"END:VALARM" . PHP_EOL .
			"END:VEVENT" . PHP_EOL;

		$ical_body = sprintf(
			$ical_body,
			$event['local_start'],
			$event['local_end'],
			$event['user_name'],
			$event['user_email'],
			$event['user_name'] . "\\n" .
				__('Phone:') . " " . $event['user_phone'] . "\\n" .
				__('Email:') . " " . $event['user_email'],
			$event['location'],
			$event['summary'],
			$event['created'],
			date("Ymd\THis"),
			// $event['alarm']
		);

		return sprintf($ical_template, $ical_body);
	}

	/**
	 * Export event in iCalendar format.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function export($id) {
		$ids = Hashids::decode($id);

		if (!is_array($ids) || count($ids) === 0) {
			abort(404);
		}

		$id = $ids[0];
		$event = Event::findOrFail($id);
		// $locale = Patient::findOrFail($event->patient_id)->locale;
		$user = User::select([
			"users.firstname",
			"users.lastname",
			"users.timezone",
			"users.email",
			"users.phone_number",
			"countries.prefix AS phone_prefix",
			"settings.duration AS session_duration",
		])
			->join("countries", "countries.id", "=", "users.phone_country_id")
			->join("settings", "settings.user_id", "=", "users.id")
			->where("users.id", "=", $event->user_id)
			->first();

		$event->created = Carbon::parse($event->created_at)->setTimezone($user->timezone)->format("Ymd\THis");
		$event->local_start = Carbon::parse($event->start)->setTimezone($user->timezone)->format("Ymd\THis");
		$event->local_end = Carbon::parse($event->start)->addMinutes($user->session_duration)->setTimezone($user->timezone)->format("Ymd\THis");
		$event->user_name = strtoupper($user->lastname) . ", " . ucfirst($user->firstname);
		$event->user_email = $user->email;
		$event->user_phone = $user->phone_prefix . " " . $user->phone_number;
		$event->summary = __("Appointment");
		$event->alarm = Carbon::parse($event->start)->subDay()->setTimezone($user->timezone)->format("Ymd\THis");

		$info = $this->getExtraInfo([
			'user_id' => $event->user_id,
			'location_id' => $event->location_id,
		]);
		$event->location = makeOneLineAddress($info['address']);

		$ical = $this->iCalendar($event->toArray());
		$filename = "rdv_" . Carbon::parse($event->start)->setTimezone($user->timezone)->format("Y-m-d_Hi");

		return (new \Illuminate\Http\Response($ical))
			->header('Content-Type', 'text/calendar')
			->header('Content-Disposition', 'inline; filename="' . $filename . '.ics"');

		// return \Illuminate\Support\Facades\Response::make($ical, 200, [
		// 	'Content-Type' => 'text/calendar',
		// 	'Content-Disposition' => 'attachment; filename="' . $filename . '.ics"',
		// ]);

		return (new \Illuminate\Http\Response($ical))
			->header('Content-Type', 'text/calendar')
			->header('Content-Disposition', 'inline; filename="' . $filename . '.ics"')
			->header("Content-Length:", strlen($ical))
			->header("Content-Description", "File Transfer")
			->header("Content-Transfer-Encoding", "binary")
			->header("Cache-Control", "must-revalidate, post-check=0, pre-check=0")
			->header("Expires", "0")
			->header("Pragma", "public");
	}
}
