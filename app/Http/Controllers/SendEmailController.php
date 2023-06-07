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
			'start' => '2023-05-31T07:30:00.000Z',
			'end' => '2023-05-31T09:00:00.000Z',
			'timezone' => 'Europe/Brussels',
			'user_firstname' => 'Masoud',
			'user_lastname' => 'Fathi',
			'patient_firstname' => 'John',
			'patient_lastname' => 'Doe',
			'patient_email' => 'masoudf46@gmail.com',
		];
		return new AppointmentReminder($event);
	}

	public function sendAppointmentEmail(Request $request) {
		// test
		if ($request->method() === 'PUT') {
			$data = $request->all();
			$action = $data['action'];
			$event = [
				'allDay' => false,
				'end' => '2023-05-31T09:00:00.000Z',
				'extendedProps' => [
					'category' => 1,
					'patient' => [
						'email' => 'masoudf46@gmail.com',
						'id' => 1,
						'name' => 'Doe, John',
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
						'email' => 'masoudf46@gmail.com',
						'id' => 1,
						'name' => 'Doe, John',
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

		// dd($request->event['extendedProps']['patient']['email']);
		// Mail::to($request->event['extendedProps']['patient']['email'])
		// 	->send(new AppointmentEmail($request->action, $request->event, $request->old_event));

		// session()->flash("success", __("The email has been sent."));
		// return back();

		// $patient = Patient::find($patient);
		// $event = Event::find($event);

		// dd($request->all());
		return new AppointmentEmail($request->action, $request->event, $request->old_event);
	}

	public function sendChangeEmail(Request $request) {
		$email = Auth::user()->email;
		$name = Auth::user()->firstname;
		Mail::to($email)
			->send(new ChangeEmail($name));
		return redirect()->route('home');
	}

	public function sendChangePassword() {
		$email = Auth::user()->email;
		$name = Auth::user()->firstname;
		Mail::to($email)
			->send(new ChangePassword($name));
		return redirect()->route('home');
	}
}
