<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentEmail;
use App\Models\Country;
use App\Models\Event;
use App\Models\Location;
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

class TestController extends Controller {
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
				'location_id' => $e->location_id,
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
	 * Get the SMS cost between two dates
	 *
	 * @param  Integer $user_id
	 * @param  String $start
	 * @param  String $end
	 * @return Array
	 */
	public function getSMSCost($user_id, $start, $end) {
		$result = ['success' =>  false];
		
		$user = User::select([
			'users.id',
			'users.firstname',
			'users.lastname',
			'users.timezone',
		])->whereId($user_id)->first();

		$start_date = new Carbon($start, $user->timezone);
		$end_date = (new Carbon($end, $user->timezone))->subSecond()->addDay();

		$events = Event::select([
			"events.id",
			"events.status",
			"event_sms.action",
			"event_sms.provider",
			"event_sms.currency",
			"event_sms.parts",
			"event_sms.cost",
			"event_sms.created_at",
			"countries.name AS country",
		])
			->join("event_sms", "event_sms.event_id", "=", "events.id")
			->join("countries", "countries.code", "=", "event_sms.country")
			->where("events.user_id", "=", $user_id)
			->whereBetween("event_sms.created_at", [$start, $end])
			->orderBy('event_sms.created_at')
			->get();

		$add = 0;
		$delete = 0;
		$update = 0;
		$remind = 0;
		$total = 0;

		foreach ($events as $event) {
			$total += $event->cost;
			$event->cost /= config('project.sms_price_multiplier');
			
			switch ($event->action) {
				case 'add':
					$add++;
					break;
				case 'update':
					$update++;
					break;
				case 'delete':
					$delete++;
					break;
				case 'remind':
					$remind++;
					break;
			}
		}
		
		$count = $events->count();
		
		// $total = 197901;

		$cost = $total * 100 / config('project.sms_price_multiplier');
		$cost = ceil($cost);
		$cost = number_format($cost / 100, 2);

		// $total = number_format($total / config('project.sms_price_multiplier'), 2);

		$first = $events->first();
		$last = $events->last();
		
		if ($first) $first = Carbon::parse($first->created_at)->format('d/m/Y H:i:s');
		if ($last) $last = Carbon::parse($last->created_at)->format('d/m/Y H:i:s');
	
		$result['data'] = compact('add', 'update', 'delete', 'remind',
			'count',
			'total',
			'cost',
			'first',
			'last',
			'user',
			'events'
		);
		$result['success'] = true;
		
		// dd($user->toArray(), $add, $update, $delete, $remind, $total, $first, $last, $events->toArray());
		dd($result['data']);

		return $result;
	}

}
