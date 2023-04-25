<?php

namespace App\Http\Controllers;

use App\Mail\ChangeEmail;
use App\Mail\ChangePassword;
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
