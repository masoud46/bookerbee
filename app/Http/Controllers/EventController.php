<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentEmail;
use App\Models\Country;
use App\Models\Event;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EventController extends Controller {
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->middleware('auth');
	}

	/**
	 * Convert database row to FullCalendar JavaScript compatible object.
	 *
	 * @param  Event $event
	 * @return Array $jsEvent
	 */
	public function dbToJs(Event $e) {
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
	 * @param  String $from
	 * @param  String $to
	 * @return \Illuminate\Http\Response
	 */
	public function get($start = null, $end = null) {
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
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		$entries = 'resources/js/pages/agenda.js';
		$prefixes = Country::select(["id", "prefix"])->get()->toArray();

		return view('agenda', compact('entries', 'prefixes'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {
		if ( // Reject the lock request if "agenda_lock" is not in user's features
			!isset($request->extendedProps['patient']['id']) &&
			!in_array('agenda_lock', Auth::user()->features)
		) {
			return response()->json(['success' => false]);
		}

		$data = $request->all()['event'];

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
				$event->byweekday = implode(',', $data['rrule']['byweekday']);
			}
		} else {
			$event->start = Carbon::parse($data['start']);
			$event->end = Carbon::parse($data['end']);
		}

		$event->save();

		$data['id'] = $event->id;

		return response()->json([
			'success' => true,
			'id' => $event->id,
			'event' => $data,
		]);
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

		$event->all_day = $data['allDay'];
		$event->title = $event->patient_id ? null : ($data['title'] ?? null);
		$event->start = Carbon::parse($data['start']);
		$event->end = Carbon::parse($data['end']);
		$event->save();

		if ($event->patient_id && isset($data['extendedProps']['patient']['email'])) {
			// Mail::to($data['extendedProps']['patient']['email'])
			// 	->send(new AppointmentEmail("update", $data, [
			// 		'localStart' => $old_event['localStart'],
			// 		'localEnd' => $old_event['localEnd'],
			// 	]));

			return redirect()->route("email.appointment", [
				'action' => "update",
				'event' => $data,
				'old_event' => [
					'localStart' => $old_event['localStart'],
					'localEnd' => $old_event['localEnd'],
				],
			]);
		}

		return response()->json(['success' => true, 'dbevent' => $event->toArray(), 'event' => $data, 'old_event' => $old_event]);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Models\Event $event
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Event $event) {
		if ($event->category === 0) {
			$event->delete();
		} else {
			$event->status = false;
			$event->save();

			// $patient = Patient::find($event->patient_id);
			// Mail::to($patient->email)->send(new AppointmentEmail('cancel', $patient, $event));
		}

		return response()->json([
			'success' => true,
			'id' => $event->id,
			'dbevent' => $event->toArray(),
		]);
	}

	/**
	 * Fetch resources between two dates.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function fetch(Request $request) {
		$events = $this->get($request->start, $request->end);

		return response()->json([
			'request' => [
				'start' => $request->start,
				'end' => $request->end,
			],
			'events' => $events,
		]);
	}
}
