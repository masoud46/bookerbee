<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		$entries = 'resources/js/pages/agenda.js';
		$prefixes = Country::select(["id", "prefix"])->get()->toArray();
		$events = Event::select([
			"events.id",
			"events.patient_id",
			"events.category",
			"events.title",
			"events.allday",
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
			->leftJoin("patients", "patients.id", "=", "events.patient_id")
			->get()->toArray();

		return view('agenda', compact('entries', 'prefixes', 'events'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {
		return response()->json(['success' => true]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Models\Event $event
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, Event $event) {
		return response()->json(['success' => true, 'event' => $event]);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Models\Event $event
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Event $event) {
		return response()->json(['success' => true, 'event' => $event]);
	}

}
