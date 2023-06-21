<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentEmail;
use App\Models\Country;
use App\Models\Event;
use App\Models\Patient;
use App\Models\Settings;
use App\Models\User;
use Carbon\Carbon;
use Hashids;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

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
	 * Convert database row to FullCalendar JavaScript compatible object.
	 *
	 * @param  Event $event
	 * @return Array
	 */
	private function dbToJs(Event $e) {
		$event = [
			'id' => $e->id,
			// 'all_day' => $e->all_day === true,
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
			}
		} else if ($e->category === 0) {
			$event['className'] = 'fc-locked-event';
		}

		if ($rrule) {
			if ($e->rrule_until) $rrule['until'] = $e->rrule_until;
			if ($e->rrule_byweekday) $rrule['byweekday'] = explode(',', $e->rrule_byweekday);

			$event['rrule'] = $rrule;
			$event['display'] = 'background';
			$event['editable'] = false;
			$event['startEditable'] = false;
		}

		if ($e->title) $event['title'] = $e->title;
		if ($e->start) $event['start'] = $e->all_day ? substr($e->start, 0, 10) : $e->start;
		if ($e->end) $event['end'] = $e->all_day ? substr($e->end, 0, 10) : $e->end;
		if ($e->duration) $event['duration'] = $e->duration;

		return $event;
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
			Country::select(["id", "prefix"])->get()->toArray(),
			"prefix",
			"id"
		);
		$db_events = Event::select([
			"events.id",
			"events.patient_id",
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
			->get();

		$events = [];
		foreach ($db_events as $e) {
			if ($e->patient_phone_number) {
				$e->patient_phone_number = "{$prefixes[$e->patient_phone_country_id]} {$e->patient_phone_number}";
			}

			$event = $this->dbToJs($e);

			$events[] = $event;
		}

		return $events;
	}

	/**
	 * Send notification email.
	 *
	 * @param  String $to
	 * @param  String $type
	 * @param  Array $event
	 * @param  Array $old_event
	 * @return Array $result
	 */
	private function sendEmail($type, $event, $old_event = null) {
		if (config('app.env') !== 'production' && !config('project.send_emails')) {
			return ['success' => true];
		}

		$result = ['success' => false];

		$provider = config('app.env') === 'production' ?
			config('project.mail.default_provider') :
			config('project.mail.default_dev_provider');

		$to = $event['extendedProps']['patient']['email'];

		try {
			Log::channel('agenda')->info("<SENDING EMAIL> {$to}");

			Mail::mailer($provider)
				->to($to)
				->send(new AppointmentEmail($type, $event, $old_event));

			$result['success'] = true;

			Log::channel('agenda')->info("<EMAIL SENT>");
		} catch (\Throwable $th) {
			Log::channel('agenda')->info("<!!! ERROR !!!>");
			Log::channel('agenda')->info($th->__toString());

			$result['error'] = $th->getMessage();
		}

		return $result;
	}

	/**
	 * Send notification SMS.
	 *
	 * @param  String $name
	 * @param  Boolean $email
	 * @param  String $to
	 * @param  String $type
	 * @param  Array $event
	 * @param  Array $old_event
	 * @return Array $result
	 */
	private function sendSMS($type, $event, $old_event = null) {
		if (config('app.env') !== 'production' && !config('project.send_sms')) {
			return ['success' => true];
		}

		$result = ['success' => false];

		$user_name = ucfirst(Auth::user()->firstname) . " " . strtoupper(Auth::user()->lastname);
		$name = ucfirst(trim(explode(",", $event['extendedProps']['patient']['name'])[1]));
		$to = $event['extendedProps']['patient']['phone'];
		$message = __("Your appointment of :date at :start with :name has been confirmed.", [
			'name' => $user_name,
			'date' => Carbon::parse($event['localStart'])->translatedFormat('l j F Y'),
			'start' => Carbon::parse($event['localStart'])->translatedFormat('H:i'),
		]);

		switch ($type) {
			case 'update':
				$message = __("Your appointment of :old_date at :old_start with :name has been rescheduled. New appointment: :date at :start.", [
					'name' => $user_name,
					'old_date' => Carbon::parse($old_event['localStart'])->translatedFormat('l j F Y'),
					'old_start' => Carbon::parse($old_event['localStart'])->translatedFormat('H:i'),
					'date' => Carbon::parse($event['localStart'])->translatedFormat('l j F Y'),
					'start' => Carbon::parse($event['localStart'])->translatedFormat('H:i'),
				]);
				break;
			case 'delete':
				$message = __("Your appointment of :date at :start with :name has been canceled.", [
					'name' => $user_name,
					'date' => Carbon::parse($event['localStart'])->translatedFormat('l j F Y'),
					'start' => Carbon::parse($event['localStart'])->translatedFormat('H:i'),
				]);
				break;
		}

		if (isset($event['extendedProps']['patient']['email'])) {
			$message .= " " . __("A detailed email has been sent to you.");
		}

		try {
			Log::channel('agenda')->info("<SENDING SMS> {$to}");

			$sms = new \App\Notifications\SmsMessage();
			$sms = $sms->to(preg_replace('/\s+/', '', $to))
				->line(__("Hello :name", ['name' => $name]) . ",")
				->line($message);

			$res = ['success' => false, 'data' => null];

			if (config('app.env') === 'production') {
				$res = $sms->send();
			} else if (config('project.send_sms')) {
				// $res = $sms->send();
				$res = $sms->dryRun()->send();
			}

			if ($res['success']) {
				Log::channel('agenda')->info("<SMS SENT>");

				$result['success'] = true;
			} else {
				Log::channel('agenda')->info("<!!! SMS ERROR !!!>");
				Log::channel('agenda')->info(print_r($res, true));

				$result['error'] = $res['data'];
			}
		} catch (\Throwable $th) {
			Log::channel('agenda')->info("<!!! ERROR !!!>");
			Log::channel('agenda')->info($th->__toString());

			$result['error'] = $th->getMessage();
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
		$prefixes = Country::select(["id", "prefix"])->get()->toArray();

		return view('agenda', compact('entries', 'settings', 'prefixes'));
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

		DB::beginTransaction();

		$event = new Event();
		$event->user_id = Auth::user()->id;
		$event->all_day = $data['allDay'];
		$event->title = isset($data['extendedProps']['patient']) ? null : ($data['title'] ?? null);

		if (isset($data['extendedProps']['patient']['id'])) {
			$event->patient_id = $data['extendedProps']['patient']['id'];
			$event->category = 1;
		} else {
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

		$data['id'] = $event->id;
		$email = $data['extendedProps']['patient']['email'] ?? null;
		$phone = $data['extendedProps']['patient']['phone'] ?? null;
		$send_sms = $phone && in_array("sms", Auth::user()->features);

		$sms_result = ['success' => true];
		$email_result = ['success' => true];
		$result = ['success' => false];
		// $result = [
		// 	'success' => false,
		// 	'id' => $event->id,
		// 	'event' => $data,
		// ];

		if ($event->patient_id && ($email || $send_sms)) {
			Log::channel('agenda')->info(
				"Event ADD: {$event->id} - email " .
					($email ? "YES" : "NO") . " - phone " .
					($phone ? "YES" : "NO") . " - sms " .
					($send_sms ? "YES" : "NO")
			);

			LaravelLocalization::setLocale($data['extendedProps']['patient']['locale']);

			if ($send_sms) {
				$sms_result = $this->sendSMS("add", $data);
			}

			if ($email) {
				$data['hash_id'] = Hashids::encode($data['id']);
				$data['user_phone'] = $this->getUserPhone();

				$email_result = $this->sendEmail("add", $data);
			}

			// Successful if either SMS or email passes
			$result['success'] = $sms_result['success'] || $email_result['success'];

			if ($result['success']) {
				$result['id'] = $event->id;

				DB::commit();
			} else {
				DB::rollBack();

				if (!$sms_result['success']) $result['sms_error'] = $sms_result['error'];
				if (!$email_result['success']) $result['email_error'] = $email_result['error'];
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

		DB::beginTransaction();

		$event->all_day = $data['allDay'];
		$event->title = $event->patient_id ? null : ($data['title'] ?? null);
		$event->start = Carbon::parse($data['start'])->format('Y-m-d H:i:s');
		$event->end = Carbon::parse($data['end'])->format('Y-m-d H:i:s');

		if ($event->category === 1) {
			$event->setReminders($data['extendedProps']['patient']);
		}

		$event->save();

		$email = $data['extendedProps']['patient']['email'] ?? null;
		$phone = $data['extendedProps']['patient']['phone'] ?? null;
		$send_sms = $phone && in_array("sms", Auth::user()->features);

		$sms_result = ['success' => true];
		$email_result = ['success' => true];
		$result = ['success' => false];
		// $result = [
		// 	'success' => true,
		// 	'dbevent' => $event->toArray(),
		// 	'event' => $data,
		// 	'old_event' => $old_event,
		// ];

		if ($event->patient_id && ($email || $send_sms)) {
			Log::channel('agenda')->info(
				"Event UPDATE: {$event->id} - email " .
				($email ? "YES" : "NO") . " - phone " .
				($phone ? "YES" : "NO") . " - sms " .
				($send_sms ? "YES" : "NO")
			);

			LaravelLocalization::setLocale($data['extendedProps']['patient']['locale']);

			if ($send_sms) {
				$sms_result = $this->sendSMS("update", $data, $old_event);
			}

			if ($email) {
				$data['hash_id'] = Hashids::encode($data['id']);
				$data['user_phone'] = $this->getUserPhone();

				$email_result = $this->sendEmail("update", $data, $old_event);
			}

			// Successful if either SMS or email passes
			$result['success'] = $sms_result['success'] || $email_result['success'];

			if ($result['success']) {
				DB::commit();
			} else {
				DB::rollBack();

				if (!$sms_result['success']) $result['sms_error'] = $sms_result['error'];
				if (!$email_result['success']) $result['email_error'] = $email_result['error'];
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

		$event->delete();

		$email = $data['extendedProps']['patient']['email'] ?? null;
		$phone = $data['extendedProps']['patient']['phone'] ?? null;
		$send_sms = $phone && in_array("sms", Auth::user()->features);

		$sms_result = ['success' => true];
		$email_result = ['success' => true];
		$result = ['success' => false];
		// $result = [
		// 	'success' => true,
		// 	'id' => $event->id,
		// 	'dbevent' => $event->toArray(),
		// ];

		if ($event->patient_id && ($email || $send_sms)) {
			Log::channel('agenda')->info(
				"Event DELETE: {$event->id} - email " .
				($email ? "YES" : "NO") . " - phone " .
				($phone ? "YES" : "NO") . " - sms " .
				($send_sms ? "YES" : "NO")
			);

			LaravelLocalization::setLocale($data['extendedProps']['patient']['locale']);

			if ($send_sms) {
				$sms_result = $this->sendSMS("delete", $data);
			}

			if ($email) {
				$email_result = $this->sendEmail("delete", $data);
			}

			// Successful if either SMS or email passes
			$result['success'] = $sms_result['success'] || $email_result['success'];

			if ($result['success']) {
				DB::commit();
			} else {
				DB::rollBack();

				if (!$sms_result['success']) $result['sms_error'] = $sms_result['error'];
				if (!$email_result['success']) $result['email_error'] = $email_result['error'];
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
		// return response()->json([
		// 	'request' => [
		// 		'start' => $request->start,
		// 		'end' => $request->end,
		// 	],
		// 	'events' => $events,
		// ]);
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
			"SUMMARY:%s" . PHP_EOL .
			"CREATED:%s" . PHP_EOL .
			"DTSTAMP:%s" . PHP_EOL .
			"TRANSP:OPAQUE" . PHP_EOL .
			"PRIORITY:1" . PHP_EOL .
			"BEGIN:VALARM" . PHP_EOL .
			"ACTION:DISPLAY" . PHP_EOL .
			"TRIGGER;VALUE=DATE-TIME:%s" . PHP_EOL .
			"END:VALARM" . PHP_EOL .
			"END:VEVENT" . PHP_EOL;

		$ical_body = sprintf(
			$ical_body,
			$event['local_start'],
			$event['local_end'],
			$event['user_name'],
			$event['user_email'],
			$event['user_name'] . "\\n" . $event['user_phone'],
			$event['summary'],
			$event['created'],
			date("Ymd\THis"),
			$event['alarm']
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
		$id = Hashids::decode($id)[0];
		$event = Event::findOrFail($id);
		$user = User::select([
			"users.firstname",
			"users.lastname",
			"users.timezone",
			"users.email",
			"users.phone_number",
			"countries.prefix AS phone_prefix",
		])
			->join("countries", "countries.id", "=", "users.phone_country_id")
			->where("users.id", "=", $event->user_id)
			->first();

		$event->created = Carbon::parse($event->created_at)->setTimezone($user->timezone)->format("Ymd\THis");
		$event->local_start = Carbon::parse($event->start)->setTimezone($user->timezone)->format("Ymd\THis");
		$event->local_end = Carbon::parse($event->end)->setTimezone($user->timezone)->format("Ymd\THis");
		$event->user_name = strtoupper($user->lastname) . ", " . ucfirst($user->firstname);
		$event->user_email = $user->email;
		$event->user_phone = $user->phone_prefix . " " . $user->phone_number;
		$event->summary = __("Appointment");
		$event->alarm = Carbon::parse($event->start)->subDay()->setTimezone($user->timezone)->format("Ymd\THis");

		$filename = "rdv_" . Carbon::parse($event->start)->setTimezone($user->timezone)->format("Y-m-d_Hi");
		$ical = $this->iCalendar($event);

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
