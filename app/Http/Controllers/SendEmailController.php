<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentEmail;
use App\Mail\AppointmentReminder;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Masoud46\LaravelApiMail\Facades\ApiMail;
use Masoud46\LaravelApiSms\Facades\ApiSms;
use Vinkla\Hashids\Facades\Hashids;

class SendEmailController extends Controller {
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->middleware('auth');
	}

	// test
	private function getMessage($locale) {
		$settings = Settings::whereUserId(Auth::user()->id)->first();
		$msg_email = $settings->msg_email ? json_decode($settings->msg_email, true) : [];

		return $msg_email[$locale] ?? array_shift($msg_email) ?? null;
	}

	// test
	public function sendReminderEmail(Request $request) {
		$event = [
			'address' => [
				'line1' => 'Route de Luxembourg 205',
				'line2' => null,
				'line3' => 'Line 3',
				'code' => '7374',
				'city' => 'Lorentzweiler',
				'country' => 'Luxembourg',
			],
			'start' => '2023-05-02 07:30:00',
			'end' => '2023-05-02 09:00:00',
			'duration' => 50,
			'remaining_time' => 26,
			'timezone' => 'Europe/Brussels',
			'user_firstname' => 'Masoud',
			'user_lastname' => 'Fathi',
			'user_email' => 'masoudf46@gmail.com',
			'user_phone_number' => '620 123 456',
			'user_phone_prefix' => '+32',
			'patient_firstname' => 'John',
			'patient_lastname' => 'Doe',
			'patient_email' => 'masoudf46@gmail.com',
		];
		$event['user_phone'] = $event['user_phone_prefix'] . " " . $event['user_phone_number'];
		$msg = $this->getMessage("fr");
		if ($msg) $event['msg_email'] = $msg;


		// /*** EMAIL ***/
		// $payload = [
		// 	'to' => 'masoudf46@gmail.com',
		// 	'subject' => 'Reminder test',
		// 	'body' => (new AppointmentReminder($event))->render(),
		// ];
		// // return ApiMail::send($payload);
		// return ApiMail::provider('sendgrid')->send($payload);

		/*** SMS ***/
		$payload = [
			'country' => 'BE',
			'to' => '+32472877055',
			'message' => "SMS ô tést...",
			'dryrun' => true,
		];

		return ApiSms::send($payload);
		// return ApiSms::provider('ovh')->send($payload);
		// return ApiSms::provider('ovh')->estimate($payload);
		// return ApiSms::provider('ovh')->balance();



		return new AppointmentReminder($event);
	}

	public function sendAppointmentEmail(Request $request) {
		// test
		if ($request->method() === 'PUT') {
			$data = $request->all();
			$action = $data['action'];
			$event = [
				'address' => [
					'line1' => __('Your residence'),
					// 'line1' => 'Route de Luxembourg 205',
					// 'line2' => null,
					// 'line3' => 'Line 3',
					// 'code' => '7374',
					// 'city' => 'Lorentzweiler',
					// 'country' => 'Luxembourg',
				],
				'user_phone' => "+352 620 123 456",
				'allDay' => false,
				'end' => '2023-05-31T09:00:00.000Z',
				'extendedProps' => [
					'category' => 1,
					'patient' => [
						'id' => 1,
						'name' => 'Doe, John',
						'locale' => 'en',
						'email' => 'masoudf46@gmail.com',
						'phone' => '+32 621 654 987',
					],
				],
				'id' => '38',
				'hash_id' => Hashids::encode(38),
				'localEnd' => '2023-05-31T11:30:00+02:00',
				'localStart' => '2023-05-31T09:30:00+02:00',
				'start' => '2023-05-31T07:00:00.000Z',
				'end' => '2023-05-31T09:00:00.000Z',
				'duration' => 50,
				'title' => 'Doe, John',
			];
			$old_event = [
				'allDay' => false,
				'end' => '2023-05-31T09:00:00.000Z',
				'extendedProps' => [
					'category' => 1,
					'patient' => [
						'id' => 1,
						'name' => 'Doe, John',
						'locale' => 'en',
						'email' => 'masoudf46@gmail.com',
						'phone' => '+32 621 654 987',
					],
				],
				'id' => '5',
				'localEnd' => '2023-05-31T11:00:00+02:00',
				'localStart' => '2023-05-31T09:00:00+02:00',
				'start' => '2023-05-31T07:00:00.000Z',
				'title' => 'Doe, John',
			];
			$msg = $this->getMessage("en");
			if ($msg && $action !== "delete") $event['msg_email'] = $msg;
			// return new AppointmentEmail($action, $event, $old_event);
			return new AppointmentEmail($action, $event);
		}

		return new AppointmentEmail($request->action, $request->event, $request->old_event);
	}
}
