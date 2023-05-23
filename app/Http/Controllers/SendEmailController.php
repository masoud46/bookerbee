<?php

namespace App\Http\Controllers;

use App\Mail\AppointmentEmail;
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

	public function sendAppointmentEmail(Request $request) {
		// Mail::to($request->patient->email)
		// 	->send(new AppointmentEmail($request));

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
