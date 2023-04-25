<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Country;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Patient;
use App\Models\Settings;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller {
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->middleware('auth');
	}

	/**
	 * Generate the reference for the invoice.
	 *
	 * @param StdClass $invoice
	 * @return String
	 */
	private function generateReference($invoice) {
		// $format = Settings::whereUserId(Auth::user()->id)->first()->ref_format;
		// $format = explode("|", $format);
		// $left = "";
		// eval('$left = date("y", 1681982675);');
		// dd(
		// 	$format,
		// 	date("y", 1681982675),
		// 	date("y", strtotime($invoice->created_at)),
		// 	sprintf('date("y", %d);', strtotime($invoice->created_at)),
		// 	$left,
		// 	eval('date("y", 1681982675);'),
		// 	eval(sprintf('date("y", %d);', strtotime($invoice->created_at))),
		// 	sprintf("%s{$format[1]}%s", eval(sprintf('date("y", %d);', strtotime($invoice->created_at))), $invoice->id)
		// );

		return sprintf('%s/%s', date('y', strtotime($invoice->created_at)), $invoice->id);
	}

	/**
	 * Get the information of the last invoice.
	 *
	 * @param StdClass $invoice
	 * @return String
	 */
	private function getLastInvoice($patient_id, $invoice_id = null) {
		$lastInvoice = Invoice::select([
			"id",
			"session",
			"doc_code",
			"doc_name",
			"doc_date",
		])
			->where(function ($query) use ($invoice_id) {
				if ($invoice_id) { // update
					return $query->where("id", "<", $invoice_id);
				} else { // create
					return $query->whereUserId(Auth::user()->id);
				}
			})
			->wherePatientId($patient_id)
			->latest()
			->first();

		if ($lastInvoice) {
			$lastInvoice->next_session = $lastInvoice->session + Appointment::whereInvoiceId($lastInvoice->id)->count();
			$lastInvoice = $lastInvoice->toArray();
		} else {
			$lastInvoice = [
				'next_session' => 1,
				'doc_code' => '',
				'doc_name' => '',
				'doc_date' => '',
			];
		}

		return $lastInvoice;
	}

	/**
	 * Get views' common data.
	 *
	 * @return Array
	 */
	private function getCommonData() {
		$settings = Settings::whereUserId(Auth::user()->id)->first();
		$locations = Location::all()->sortBy('code');
		$types = Type::all()->sortBy('code');
		$countries = Country::sortedList();

		$settings->amount = currency_format($settings->amount);

		if (!User::hasSecondaryAddress()) {
			$bisId = array_search("009b", array_column($locations->toArray(), "code"));
			$locations[$bisId]['disabled'] = true;
		}

		return [
			'settings' => $settings,
			'countries' => $countries,
			'locations' => $locations,
			'types' => $types,
		];
	}

	/**
	 * Get an invoice and its related patient and appointments
	 *
	 * @param  Integer $id
	 * @param  Boolean $fraction
	 * @return Array
	 */
	protected function getInvoice($id, $fraction = false) {
		$invoice = Invoice::whereId($id)->whereUserId(Auth::user()->id);
		if (!$invoice) {
			abort(404);
		}

		$invoice = DB::table("invoices")->select([
			"invoices.id",
			"invoices.user_id",
			"invoices.patient_id",
			"invoices.session",
			"invoices.name",
			"invoices.acc_number",
			"invoices.acc_date",
			"invoices.doc_code",
			"invoices.doc_name",
			"invoices.doc_date",
			"invoices.prepayment",
			"invoices.granted_at",
			"invoices.location_check",
			"invoices.location_name",
			"invoices.location_address",
			"invoices.location_code",
			"invoices.location_city",
			"invoices.location_country_id",
			"invoices.created_at",
			"invoice_location_country.name AS location_country",
			"patients.category AS patient_category",
			"patients.code AS patient_code",
			"patients.firstname AS patient_firstname",
			"patients.lastname AS patient_lastname",
			"patients.email AS patient_email",
			"patients.phone_number AS patient_phone_number",
			"patients.address_line1 AS patient_address_line1",
			"patients.address_line2 AS patient_address_line2",
			"patients.address_line3 AS patient_address_line3",
			"patients.address_code AS patient_address_code",
			"patients.address_city AS patient_address_city",
			"patients.address_country_id AS patient_address_country_id",
			"patient_address_country.name AS patient_address_country",
			"patient_phone_country.prefix AS patient_phone_prefix",
		])
			->where("invoices.id", "=", $id)
			->join("patients", "patients.id", "=", "invoices.patient_id")
			->join("countries AS patient_address_country", "patient_address_country.id", "=", "patients.address_country_id")
			->leftJoin("countries AS patient_phone_country", "patient_phone_country.id", "=", "patients.phone_country_id")
			->leftJoin("countries AS invoice_location_country", "invoice_location_country.id", "=", "invoices.location_country_id")
			->first();

		if (!$invoice) {
			abort(404);
		}

		$appointments = Appointment::select([
			"appointments.*",
			"locations.code AS location_code",
			"locations.description AS location_description",
			"types.code AS type_code",
			"types.description AS type_description",
		])
			->whereInvoiceId($id)
			->join("locations", "locations.id", "=", "appointments.location_id")
			->join("types", "types.id", "=", "appointments.type_id")
			->orderBy("appointments.id")
			->get();
		// if ($appointments->count() === 0) { abort(404); } // for dummy data, can be removed for production

		$invoice->total_amount = 0;
		$invoice->total_insurance = 0;
		foreach ($appointments as $key => $value) {
			if ($invoice->patient_category === 1) {
				$value->description = $value->type_description;
			}
			unset($value->type_description);

			if ($value->amount) {
				$invoice->total_amount += $value->amount;
				$appointments[$key]->amount = currency_format($value->amount, $fraction);
			}

			if ($value->insurance) {
				$invoice->total_insurance += $value->insurance;
				$appointments[$key]->insurance = currency_format($value->insurance, $fraction);
			}
		}

		$invoice->total_to_pay = $invoice->total_amount;
		if ($invoice->prepayment) {
			$invoice->total_to_pay = $invoice->total_amount - $invoice->prepayment;
			$invoice->prepayment = currency_format($invoice->prepayment, $fraction);
		}

		$invoice->total_amount = $invoice->total_amount > 0 ? currency_format($invoice->total_amount, $fraction) : null;
		$invoice->total_insurance = $invoice->total_insurance > 0 ? currency_format($invoice->total_insurance, $fraction) : null;
		$invoice->total_to_pay = $invoice->total_to_pay > 0 ? currency_format($invoice->total_to_pay, $fraction) : null;

		$invoice->reference = $this->generateReference($invoice);

		// $patient_sessions = Patient::getPrevSessions($invoice->patient_id, $invoice->created_at);
		// $invoice->patient_sessions = $patient_sessions;
		$invoice->patient_address_country = __($invoice->patient_address_country);

		$invoice->editable = $id === Invoice::getLastId($invoice->patient_id);

		$lastInvoice = $this->getLastInvoice($invoice->patient_id, $invoice->id);

		$key = Crypt::encrypt([
			'invoice_id' => $invoice->id,
			'patient_id' => $invoice->patient_id,
			'initial_session' => $lastInvoice['next_session'],
		]);

		return [
			'key' => $key,
			'invoice' => $invoice,
			'appointments' => $appointments,
			'lastInvoice' => $lastInvoice,
		];
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @param Integer $limit
	 * @return \Illuminate\Http\Response
	 */
	public function index($limit = null) {
		$entries = 'resources/js/pages/index.js';

		$patients_count = Patient::whereUserId(Auth::user()->id)->count();
		$count = Invoice::whereUserId(Auth::user()->id)->count();
		$years = Invoice::select(DB::raw('YEAR(created_at) AS year'))
			->whereUserId(Auth::user()->id)
			// ->latest()
			->groupBy("year")
			->get();

		if (!is_numeric($limit)) {
			$limit = 3;
		} else {
			$limit = intval($limit);
		}

		$invoices = DB::table('invoices')->select([
			"invoices.created_at",
			"invoices.id",
			"invoices.patient_id",
			DB::raw('DATE_FORMAT(invoices.created_at, "%d/%m/%Y") AS date'),
			"invoices.name",
			"patients.category AS patient_category",
			DB::raw('CONCAT(patients.code, " - ", patients.lastname, ", ", patients.firstname) AS patient'),
			DB::raw('SUM(appointments.amount) AS total'),
		])
			->where("invoices.user_id", "=", Auth::user()->id)
			->when($limit < 1000, function ($query) use ($limit) {
				// $query->where("invoices.created_at", ">=", (new \Carbon\Carbon)->setDay(1)->setTime(0, 0, 0)->submonths($limit - 1));
				$query->where("invoices.created_at", ">=", Carbon::now()->setDay(1)->setTime(0, 0, 0)->submonths($limit - 1));
			})
			->when($limit >= 1000, function ($query) use ($limit) {
				$query->whereYear('invoices.created_at', $limit);
			})
			->join("patients", "patients.id", "=", "invoices.patient_id")
			->join("appointments", "appointments.invoice_id", "=", "invoices.id")
			->groupBy("id", "patient_id", "created_at", "date", "name", "patient", "patient_category")
			->latest()
			->get();

		foreach ($invoices as $invoice) {
			$invoice->total = currency_format($invoice->total, true);
			$invoice->reference = $this->generateReference($invoice);
			$invoice->editable = $invoice->id === Invoice::getLastId($invoice->patient_id);
		}

		// after signing in,
		// set user's phone and fax prefix as well as the address country name
		// $route = app('router')->getRoutes()->match( // get previous url's route
		// 	app('request')->create(url()->previous())
		// );
		// if ($route->uri === "login") {
		// }

		return view('invoice-index', [
			'entries' => $entries,
			'patients_count' => $patients_count,
			'years' => $years,
			'count' => $count,
			'invoices' => $invoices,
		]);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @param  \App\Models\Patient  $patient
	 * @return \Illuminate\Http\Response
	 */
	public function create(Patient $patient) {
		if ($patient->user_id !== Auth::user()->id) {
			abort(404);
		}

		$entries = 'resources/js/pages/invoice.js';

		$invoice_object = array_merge($this->getCommonData(), [
			'entries' => $entries,
		]);

		// $sessions = Patient::getPrevSessions($patient->id);
		// $patient->sessions = $sessions;
		$patient->name = sprintf("%s, %s", $patient->lastname, $patient->firstname);

		if ($patient->phone_country_id) {
			$patient->phone_prefix = Country::find($patient->phone_country_id)->prefix;
		}

		$lastInvoice = $this->getLastInvoice($patient->id);

		$key = Crypt::encrypt([
			'patient_id' => $patient->id,
			'initial_session' => $lastInvoice['next_session'],
		]);

		unset($patient->id);
		unset($patient->created_at);
		unset($patient->updated_at);

		$invoice_object = array_merge($invoice_object, [
			'key' => $key,
			'patient' => $patient,
			'lastInvoice' => $lastInvoice,
		]);

		return view('invoice', $invoice_object);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {
		$params = $request->all();

		if (!isset($params['form-key'])) {
			session()->flash("error", __("Form key not found."));
			return back()->withInput();
		}

		try {
			$form_key = Crypt::decrypt($params['form-key']);
		} catch (\Throwable $th) {
			session()->flash("error", __("Form key is corrupted."));
			return back()->withInput();
		}
		unset($params['form-key']);

		if (!is_array($form_key) || !isset($form_key['patient_id']) || !isset($form_key['initial_session'])) {
			session()->flash("error", __("Form key is invalid."));
			return back()->withInput();
		}

		$user_id = Auth::user()->id;
		$is_update = isset($form_key['invoice_id']);

		if ($is_update) { // update request
			$invoice = Invoice::find($form_key['invoice_id']);
			if ($invoice === null || $invoice->user_id !== $user_id) {
				session()->flash("error", __("Invoice not found."));
				return back()->withInput();
			}
		}

		if (!isset($params['invoice-location_check'])) {
			$params['invoice-location_check'] = null;
			$params['invoice-location_name'] = null;
			$params['invoice-location_address'] = null;
			$params['invoice-location_code'] = null;
			$params['invoice-location_city'] = null;
		} else {
			$params['invoice-location_check'] = true;
		}

		$patient_object =  [];
		$invoice_object =  [];
		$apps = [];

		$currency_regex = currency_regex();

		foreach ($params as $key => $value) {
			$field = explode("-", $key, 2);
			switch ($field[0]) {
				case "invoice":
					$invoice_object[$field[1]] = $value;
					break;
				case "patient":
					$patient_object[$field[1]] = $value;
					break;
				case "app":
					$app_field = explode("-", $field[1]);
					$apps[$app_field[1]][$app_field[0]] = [
						'key' => $key,
						'value' => $value,
					];
					break;
			}
		}

		$params_rules = [
			'patient-firstname' => "required",
			'patient-lastname' => "required",
			'patient-address_line1' => "required",
			'patient-address_code' => "required",
			'patient-address_city' => "required",
			'patient-address_country_id' => "required|numeric",
			'invoice-session' => "required|gte:{$form_key['initial_session']}",
			'invoice-name' => "required",
			'invoice-acc_number' => "",
			'invoice-acc_date' => "nullable|date",
			'invoice-doc_code' => "required",
			'invoice-doc_date' => "required|date",
			'invoice-prepayment' => "nullable|regex:{$currency_regex}",
			'invoice-granted_at' => "nullable|date",
		];

		$params_messages = [
			'patient-firstname.required' => app('ERRORS')['required'],
			'patient-lastname.required' => app('ERRORS')['required'],
			'patient-address_line1.required' => app('ERRORS')['required'],
			'patient-address_code.required' => app('ERRORS')['required'],
			'patient-address_city.required' => app('ERRORS')['required'],
			'patient-address_country_id.required' => app('ERRORS')['required'],
			'patient-address_country_id.numeric' => app('ERRORS')['numeric'],
			'invoice-session.required' => app('ERRORS')['required'],
			'invoice-session.gte' => str_replace(':initial_session', $form_key['initial_session'], app('ERRORS')['session']),
			'invoice-name.required' => app('ERRORS')['required'],
			'invoice-acc_date.date' => app('ERRORS')['date'],
			'invoice-doc_code.required' => app('ERRORS')['required'],
			'invoice-doc_date.required' => app('ERRORS')['required'],
			'invoice-doc_date.date' => app('ERRORS')['date'],
			'invoice-prepayment.regex' => app('ERRORS')['regex']['price'],
			'invoice-granted_at.date' => app('ERRORS')['date'],
			'invoice-location.required' => app('ERRORS')['all_required'],
			'invoice-location_country_id.numeric' => app('ERRORS')['numeric'],
		];

		if (count($apps) === 0) {
			session()->flash("error", __("No session!"));
			return back()->withInput();
		}

		$visible_apps = [];
		for ($i = 0; $i < count($apps); $i++) {
			if ($apps[$i]['visible']['value'] ?? false) {
				unset($apps[$i]['visible']);
				$visible_apps[] = $apps[$i];
				foreach ($apps[$i] as $key => $items) {
					unset($date);
					unset($regex);
					if ($key === "done_at") {
						$date = "|date";
						$params_messages["{$items['key']}.date"] = app('ERRORS')['date'];
					}
					if (in_array($key, ["amount", "insurance"])) {
						$regex = "|regex:{$currency_regex}";
						$params_messages["{$items['key']}.regex"] = app('ERRORS')['regex']['price'];
					}
					$params[$items['key']] = $items['value'];
					$params_rules[$items['key']] =
						($key === "insurance" ? "nullable" : "required") .
						($regex ?? "") .
						($date ?? "");
					if (in_array($key, ["amount", "insurance"])) {
						$params_messages["{$items['key']}.required"] = " ";
					} else {
						$params_messages["{$items['key']}.required"] = app('ERRORS')['required'];
					}
				}
			}
		}

		if (count($visible_apps) === 0) {
			session()->flash("error", __("No visible session!"));
			return back()->withInput();
		}

		if ($params['invoice-location_check']) {
			$params['invoice-location'] =
				$params['invoice-location_name'] &&
				$params['invoice-location_address'] &&
				$params['invoice-location_code'] &&
				$params['invoice-location_city'] &&
				$params['invoice-location_country_id']
				? true
				: null;
			$params_rules['invoice-location'] = "required";
			$params_rules['invoice-location_name'] = "required";
			$params_rules['invoice-location_address'] = "required";
			$params_rules['invoice-location_code'] = "required";
			$params_rules['invoice-location_city'] = "required";
			$params_rules['invoice-location_country_id'] = "required|numeric";
		}

		$validator = Validator::make($params, $params_rules, $params_messages);

		if ($validator->fails()) {
			session()->flash("error", app('ERRORS')['form']);
			return back()->withErrors($validator->errors())->withInput();
		}

		$invoice_object['patient_id'] = $form_key['patient_id'];

		$patient = Patient::find($form_key['patient_id']);
		if ($patient === null) {
			session()->flash("error", __("Patient not found."));
			return back()->withInput();
		}

		if (!$is_update) { // create request
			$invoice = new Invoice();
			$invoice->user_id = $user_id;
		}

		foreach ($patient_object as $key => $value) {
			$patient[$key] = $value;
		}
		$patient->save();

		foreach ($invoice_object as $key => $value) {
			$invoice[$key] = $value;
		}
		if ($invoice['prepayment']) {
			$prepayment = currency_parse($invoice['prepayment']);
			$invoice['prepayment'] = intval($prepayment);
		}
		$invoice->save();

		// get all the appointments of the invoice
		$appointments = Appointment::whereInvoiceId($invoice->id)
			->orderBy("id")
			->get();

		for ($i = 0; $i < count($visible_apps); $i++) {
			if ($appointments->count()) {
				$app = $appointments->first();
				$first_key = $appointments->keys()->first();
				$appointments = $appointments->forget($first_key);
			} else {
				$app = new Appointment();
			}

			foreach ($visible_apps[$i] as $key => $field) {
				if (in_array($key, ["amount", "insurance"])) {
					$field['value'] = intval(currency_parse($field['value']));

					if ($field['value'] === 0) {
						$field['value'] = null;
					}
				}

				$app[$key] = $field['value'];
			}

			// remember: disabled inputs are not in POST/PUT query ("description" here)
			if (!isset($visible_apps[$i]['description'])) $app->description = null;
			if (!isset($visible_apps[$i]['insurance'])) $app->insurance = null;

			$app->invoice_id = $invoice->id;
			$app->save();
		}

		// delete any extra old appointments from database
		foreach ($appointments as $appointment) {
			$appointment->delete();
		}

		if ($is_update) {
			session()->flash("success", __("The invoice has been updated."));
			return back()->withInput();
		}

		session()->flash("success", __("The new invoice has been saved."));
		return redirect()->route("invoice.show", [
			'invoice' => $invoice->id,
		]);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  \App\Models\Invoice  $invoice
	 * @return \Illuminate\Http\Response
	 */
	public function show(Invoice $invoice) {
		if ($invoice->user_id !== Auth::user()->id) {
			abort(404);
		}

		$entries = 'resources/js/pages/invoice.js';

		$invoice_object = $this->getInvoice($invoice->id);

		return view('invoice', array_merge($this->getCommonData(), [
			'entries' => $entries,
			'key' => $invoice_object['key'],
			'invoice' => $invoice_object['invoice'],
			'appointments' => $invoice_object['appointments'],
			'lastInvoice' => $invoice_object['lastInvoice'],
		]));
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  \App\Models\Invoice  $invoice
	 * @return \Illuminate\Http\Response
	 */
	public function edit(Invoice $invoice) {
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Models\Invoice  $invoice
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, Invoice $invoice) {
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Models\Invoice  $invoice
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Invoice $invoice) {
		//
	}

	/**
	 * Get invoice list with matched pattern based on patient's code, last name and first name.
	 * *** NOT USED ANYMORE ***
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function autocomplete(Request $request) {
		$str = $request->str;
		$invoices = DB::table('invoices')->select([
			"invoices.created_at",
			"invoices.id",
			DB::raw('DATE_FORMAT(invoices.created_at, "%d/%m/%Y") AS date'),
			"invoices.name",
			"patients.code",
			DB::raw('CONCAT(patients.lastname, ", ", patients.firstname) AS patient'),
			DB::raw('SUM(appointments.amount) AS total'),
		])
			->join("patients", "patients.id", "=", "invoices.patient_id")
			->where("invoices.user_id", "=", Auth::user()->id)
			->where(function ($query) use ($str) {
				$query
					->where("patients.lastname", "LIKE", "%{$str}%")
					->orWhere("patients.firstname", "LIKE", "%{$str}%")
					->orWhere("patients.code", "LIKE", "{$str}%");
			})
			->leftJoin("appointments", "appointments.invoice_id", "=", "invoices.id")
			->groupBy("id", "created_at", "date", "name", "patients.code", "patient")
			->latest()
			->get()->toArray();

		foreach ($invoices as $invoice) {
			$invoice->total = currency_format($invoice->total, true);
			unset($invoice->created_at);
		}

		return response()->json($invoices);
	}

	/**
	 * Display the specified invoice to print.
	 *
	 * @param  \App\Models\Invoice  $invoice
	 * @return \Illuminate\Http\Response
	 */
	public function print(Invoice $invoice) {
		if ($invoice->user_id !== Auth::user()->id) {
			abort(404);
		}

		$invoice_object = $this->getInvoice($invoice->id, true);

		$user = Auth::user();
		$countries = Country::select("id", "prefix", "name")
			->whereId($user->phone_country_id)
			->orWhere("id", "=", $user->fax_country_id)
			->orWhere("id", "=", $user->address_country_id)
			->orWhere("id", "=", $user->address2_country_id)
			->get();

		foreach ($countries as $country) {
			if ($country->id === $user->phone_country_id) $user->phone_prefix = $country->prefix;
			if ($country->id === $user->fax_country_id) $user->fax_prefix = $country->prefix;
			if ($country->id === $user->address_country_id) $user->address_country = __($country->name);
			if ($country->id === $user->address2_country_id) $user->address2_country = __($country->name);
		}

		// if user has a secondary address AND
		// if any of the appointments' location is "009b", use the secondary address
		if (User::hasSecondaryAddress()) {
			foreach ($invoice_object['appointments'] as $app) {
				if ($app->location_code === "009b") {
					$user->address_line1 = $user->address2_line1;
					$user->address_line2 = $user->address2_line2;
					$user->address_line3 = $user->address2_line3;
					$user->address_code = $user->address2_code;
					$user->address_city = $user->address2_city;
					$user->address_country_id = $user->address2_country_id;
					$user->address_country = $user->address2_country;
					break;
				}
			}
		}

		$invoice_object['user'] = $user;
		$invoice_object['invoice']->reference = $this->generateReference($invoice_object['invoice']);

		return view('print-invoice', $invoice_object);
	}
}
