<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentEmail;
use App\Mail\AppointmentReminder;
use App\Mail\ChangeEmail;
use App\Mail\ChangePassword;
use App\Models\Event;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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
	public function sendReminderEmail(Request $request) {
		$event = [
			'start' => '2023-05-31 07:30:00',
			'end' => '2023-05-31 09:00:00',
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
		return new AppointmentReminder($event);
	}

	public function sendAppointmentEmail(Request $request) {
		// test
		if ($request->method() === 'PUT') {
			$data = $request->all();
			$action = $data['action'];
			$event = [
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
				'id' => '5',
				'hash_id' => \Hashids::encode(5),
				'localEnd' => '2023-05-31T11:30:00+02:00',
				'localStart' => '2023-05-31T09:30:00+02:00',
				'start' => '2023-05-31T07:00:00.000Z',
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
			return new AppointmentEmail($action, $event, $old_event);
		}

		return new AppointmentEmail($request->action, $request->event, $request->old_event);
	}

}
