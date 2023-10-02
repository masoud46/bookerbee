<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller {
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->middleware('auth')->except('expired');
	}

	/**
	 * Show admin dashboard.
	 *
	 * @return \Illuminate\Contracts\Support\Renderable
	 */
	public function index() {
		// after signing in,
		// if (url()->previous() === route("login")) { }

		switch (Auth::user()->status) {
			case -1: // suspended
				return redirect()->route("account.suspended");
				break;
			case 0: // inactive
				return redirect()->route("account.profile");
				break;
		}

		return redirect()->route("invoices");
	}
}
