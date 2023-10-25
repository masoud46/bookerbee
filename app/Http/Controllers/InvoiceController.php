<?php

namespace App\Http\Controllers;

use App\Models\Session;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use App\Exports\InvoiceExportView;
use Maatwebsite\Excel\Facades\Excel;

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

		return sprintf('%s/%s', date('y', strtotime($invoice->created_at)), $invoice->serial);
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
			->whereActive(true)
			->wherePatientId($patient_id)
			->where(function ($query) use ($invoice_id) {
				if ($invoice_id) { // update
					return $query->where("id", "<", $invoice_id);
				} else { // create
					return $query->whereUserId(Auth::user()->id);
				}
			})
			->latest()
			->first();

		if ($lastInvoice) {
			$lastInvoice->next_session = $lastInvoice->session + Session::whereInvoiceId($lastInvoice->id)->count();
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
		$types = Type::all()->sortBy('code');
		$locations = Location::fetchAll();
		$countries = Country::sortedList();

		$settings->amount = currency_format($settings->amount);

		return compact(
			'settings',
			'countries',
			'locations',
			'types',
		);
	}

	/**
	 * Get an invoice and its related patient and sessions
	 *
	 * @param  Integer $id
	 * @param  Boolean $fraction
	 * @return Array
	 */
	protected function getInvoice($id, $fraction = false, $params = null, $isPrint = false) {
		$invoice = Invoice::whereId($id)->whereUserId(Auth::user()->id);
		if (!$invoice) {
			abort(404);
		}

		$settings = Settings::whereUserId(Auth::user()->id)->first();

		$invoice = DB::table("invoices")->select([
			"invoices.id",
			"invoices.user_id",
			"invoices.patient_id",
			"invoices.user_address",
			"invoices.patient_address",
			"invoices.serial",
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
			"invoices.active",
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

		$sessions = Session::select([
			"sessions.*",
			"locations.code AS location_code",
			"locations.description AS location_description",
			"types.code AS type_code",
			"types.description AS type_description",
		])
			->whereInvoiceId($id)
			->join("locations", "locations.id", "=", "sessions.location_id")
			->join("types", "types.id", "=", "sessions.type_id")
			->orderBy("sessions.id")
			->get();
		// if ($sessions->count() === 0) { abort(404); } // for dummy data, can be removed for production

		$invoice->total_amount = 0;
		$invoice->total_insurance = 0;
		foreach ($sessions as $key => $value) {
			if ($invoice->patient_category === 1) {
				$value->description = $isPrint ?
					__($value->type_description, [], 'fr') :
					__($value->type_description);
			}
			unset($value->type_description);

			if ($value->amount) {
				$invoice->total_amount += $value->amount;
				$sessions[$key]->amount = currency_format($value->amount, $fraction, $params);
			}

			if ($value->insurance) {
				$invoice->total_insurance += $value->insurance;
				$sessions[$key]->insurance = currency_format($value->insurance, $fraction, $params);
			}
		}

		$invoice->total_to_pay = $invoice->total_amount;
		if ($invoice->prepayment) {
			$invoice->total_to_pay = $invoice->total_amount - $invoice->prepayment;
			$invoice->prepayment = currency_format($invoice->prepayment, $fraction, $params);
		}

		$invoice->total_amount = $invoice->total_amount > 0 ? currency_format($invoice->total_amount, $fraction, $params) : null;
		$invoice->total_insurance = $invoice->total_insurance > 0 ? currency_format($invoice->total_insurance, $fraction, $params) : null;
		$invoice->total_to_pay = $invoice->total_to_pay > 0 ? currency_format($invoice->total_to_pay, $fraction, $params) : null;

		$invoice->patient_address_country = __($invoice->patient_address_country);
		$invoice->editable = $id === Invoice::getLastActiveId($invoice->patient_id);
		$lastInvoice = $this->getLastInvoice($invoice->patient_id, $invoice->id);
		$invoice->reference = $this->generateReference($invoice);
		$invoice->date = Carbon::parse($invoice->created_at)
			->timezone(Auth::user()->timezone)
			->format('d/m/Y');

		$invoice->doc_checked = $invoice->doc_code !== null;

		$key = Crypt::encrypt([
			'invoice_id' => $invoice->id,
			'patient_id' => $invoice->patient_id,
			'initial_session' => $lastInvoice['next_session'],
		]);

		return [
			'key' => $key,
			'settings' => $settings,
			'invoice' => $invoice,
			'sessions' => $sessions,
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

		// Note: intval('all') = 0
		$limit = $limit ? intval($limit) : config('project.load_invoice_limits')[0];

		$from = null;
		$to = null;
		// convert limit according to user's timezone
		if ($limit > 0 && $limit < 1000) { // months
			$from = Carbon::now()
				->setTimezone(Auth::user()->timezone)
				->setTime(0, 0, 0)
				->setDay(1)
				->submonths($limit - 1)
				->setTimezone('UTC');
			$invoice = (DB::table('invoices')->select(["created_at"])->limit(1)->get()->toArray())[0]->created_at;
		} else if ($limit >= 1000) { // year
			$from = (new Carbon("{$limit}-01-01 00:00:00", Auth::user()->timezone))
				->setTimezone('UTC');
			$to = (new Carbon("{$limit}-12-31 23:59:59", Auth::user()->timezone))
				->setTimezone('UTC');
		}

		$patients_count = Patient::whereUserId(Auth::user()->id)->count();
		$years = Invoice::select(DB::raw('YEAR(created_at) AS year'))
			->whereUserId(Auth::user()->id)
			->groupBy("year")
			->orderBy("year", "desc")
			->get();

		$invoices = DB::table('invoices')->select([
			"invoices.created_at",
			"invoices.active",
			"invoices.id",
			"invoices.patient_id",
			"invoices.serial",
			"invoices.session",
			"invoices.name",
			DB::raw('CONCAT(patients.code, " - ", patients.lastname, ", ", patients.firstname) AS patient'),
			"patients.category AS patient_category",
			DB::raw('SUM(sessions.amount) AS total'),
		])
			->where("invoices.user_id", "=", Auth::user()->id)
			->when($limit > 0 && $limit < 1000, function ($query) use ($from) {
				$query->where("invoices.created_at", ">=", $from);
			})
			->when($limit >= 1000, function ($query) use ($from, $to) {
				$query
					->where("invoices.created_at", ">=", $from)
					->where("invoices.created_at", "<=", $to);
			})
			->join("patients", "patients.id", "=", "invoices.patient_id")
			// We use left join in case that there has been an interruption
			// between storing the invoice and storing it's sessions,
			// which the result is an invoice with no sessions.
			->leftJoin("sessions", "sessions.invoice_id", "=", "invoices.id")
			->groupBy("created_at", "active", "id", "patient_id", "serial", "session", "name", "patient", "patient_category")
			->latest()
			->get();

		foreach ($invoices as $invoice) {
			$invoice->date = Carbon::parse($invoice->created_at)
				->timezone(Auth::user()->timezone)
				->format('d/m/Y');
			$invoice->total = currency_format($invoice->total, true);
			$invoice->reference = $this->generateReference($invoice);
			$invoice->editable = $invoice->id === Invoice::getLastActiveId($invoice->patient_id);
		}

		return view('invoice-index', compact(
			'entries',
			'patients_count',
			'years',
			'invoices',
		));
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

		if (!$patient->isProfileComplete()) {
			session()->flash('incomplete_profile');
			return redirect()->route('patient.show', ['patient' => $patient->id]);
		}

		$entries = 'resources/js/pages/invoice.js';

		$invoice_object = array_merge($this->getCommonData(), [
			'entries' => $entries,
		]);

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

		$user = Auth::user();
		$is_update = isset($form_key['invoice_id']);

		if ($is_update) { // update request
			$invoice = Invoice::find($form_key['invoice_id']);
			if ($invoice === null || $invoice->user_id !== $user->id) {
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
		$sessions = [];

		$currency_regex = currency_regex();

		foreach ($params as $key => $value) {
			$field = explode("-", $key, 2);
			switch ($field[0]) {
				case "invoice":
					if ($field[1] !== 'doc_checked') {
						$invoice_object[$field[1]] = $value;
					}
					break;
				case "patient":
					$patient_object[$field[1]] = $value;
					break;
				case "session":
					$session_field = explode("-", $field[1]);
					$sessions[$session_field[1]][$session_field[0]] = [
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
			'invoice-acc_date' => "nullable|date_format:Y-m-d",
			'invoice-prepayment' => "nullable|regex:{$currency_regex}",
			'invoice-granted_at' => "nullable|date_format:Y-m-d",
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
			'invoice-acc_date.date_format' => app('ERRORS')['date'],
			'invoice-doc_code.required' => app('ERRORS')['required'],
			'invoice-doc_date.required' => app('ERRORS')['required'],
			'invoice-doc_date.date_format' => app('ERRORS')['date'],
			'invoice-prepayment.regex' => app('ERRORS')['regex']['price'],
			'invoice-granted_at.date_format' => app('ERRORS')['date'],
			'invoice-location.required' => app('ERRORS')['all_required'],
			'invoice-location_country_id.numeric' => app('ERRORS')['numeric'],
		];

		if (count($sessions) === 0) {
			session()->flash("error", __("No session!"));
			return back()->withInput();
		}

		$visible_sessions = [];
		for ($i = 0; $i < count($sessions); $i++) {
			if ($sessions[$i]['visible']['value'] ?? false) {
				unset($sessions[$i]['visible']);
				$visible_sessions[] = $sessions[$i];
				foreach ($sessions[$i] as $key => $items) {
					unset($date);
					unset($regex);
					if ($key === "done_at") {
						$date = "|date_format:Y-m-d";
						$params_messages["{$items['key']}.date_format"] = app('ERRORS')['date'];
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

		if (count($visible_sessions) === 0) {
			session()->flash("error", __("No visible session!"));
			return back()->withInput();
		}

		$doc_checked = isset($params['invoice-doc_checked']);

		if ($doc_checked) {
			$params_rules['invoice-doc_code'] = "required";
			$params_rules['invoice-doc_date'] = "required|date_format:Y-m-d";
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

		if (!$doc_checked) {
			$request->merge([
				'invoice-doc_code' => null,
				'invoice-doc_name' => null,
				'invoice-doc_date' => null,
			]);
			$invoice_object['doc_code'] = null;
			$invoice_object['doc_name'] = null;
			$invoice_object['doc_date'] = null;
		}

		DB::beginTransaction();

		try {
			$patient = Patient::find($form_key['patient_id']);
			if ($patient === null) {
				session()->flash("error", __("Patient not found."));
				return back()->withInput();
			}

			if (!$is_update) { // create request
				$invoice = new Invoice();
				$invoice->user_id = $user->id;
				$invoice->serial = Invoice::whereUserId($user->id)->count() + 1;
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

			// get all the sessions of the invoice
			$sessions = Session::whereInvoiceId($invoice->id)
				->orderBy('id')
				->get();

			for ($i = 0; $i < count($visible_sessions); $i++) {
				if ($sessions->count()) {
					$session = $sessions->first();
					$first_key = $sessions->keys()->first();
					$sessions = $sessions->forget($first_key);
				} else {
					$session = new Session();
				}

				foreach ($visible_sessions[$i] as $key => $field) {
					if (in_array($key, ["amount", "insurance"])) {
						$field['value'] = intval(currency_parse($field['value']));

						if ($field['value'] === 0) {
							$field['value'] = null;
						}
					}

					$session[$key] = $field['value'];
				}

				// remember: disabled inputs are not in POST/PUT query ("description" here)
				if (!isset($visible_sessions[$i]['description'])) $session->description = null;
				if (!isset($visible_sessions[$i]['insurance'])) $session->insurance = null;

				$session->invoice_id = $invoice->id;
				$session->save();
			}

			// delete any extra old sessions from database
			foreach ($sessions as $session) {
				$session->delete();
			}

			$countries = array_column(Country::get()->toArray(), 'name', 'id');
			$invoice->user_address = makeInvoiceAddress([
				'line1' => $user->address_line1,
				'line2' => $user->address_line2,
				'line3' => $user->address_line3,
				'code' => $user->address_code,
				'city' => $user->address_city,
				'country' => $countries[$user->address_country_id],
			]);
			$invoice->patient_address = makeInvoiceAddress([
				'line1' => $patient->address_line1,
				'line2' => $patient->address_line2,
				'line3' => $patient->address_line3,
				'code' => $patient->address_code,
				'city' => $patient->address_city,
				'country' => $countries[$patient->address_country_id],
			]);
			$invoice->save();

			DB::commit();
		} catch (\Throwable $th) {
			DB::rollBack();
			Log::debug($th->__toString());

			session()->flash("error", app('ERRORS')['form2']);
			return back()->withErrors($validator->errors())->withInput();
		}

		// if ($is_update) {
		// 	session()->flash("success", __("The invoice has been updated."));
		// } else {
		// 	session()->flash("success", __("The new invoice has been saved."));
		// }

		// return redirect()->route("invoices");

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
			'sessions' => $invoice_object['sessions'],
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
	 * Disable the specified resource.
	 *
	 * @param  \App\Models\Invoice  $invoice
	 * @return \Illuminate\Http\Response
	 */
	public function disable(Invoice $invoice) {
		if ($invoice->user_id !== Auth::user()->id) {
			abort(404);
		}

		$invoice->active = false;
		$invoice->save();
		session()->flash("success", __("The invoice has been disabled."));

		return back()->withInput();
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

		$currency_params = app('DEFAULT_CURRENCY_PARAMS');
		$currency_params['grouping_used'] = true;

		$invoice_object = $this->getInvoice($invoice->id, true, $currency_params, true);

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
		// if any of the sessions' location is "009b", use the secondary address
		if (User::hasSecondaryAddress()) {
			foreach ($invoice_object['sessions'] as $session) {
				if ($session->location_code === "009b") {
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

	/**
	 * Get the invoices between two dates.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function report(Request $request, $start = null, $end = null) {

		function hide_info(string $string, int $visible, string $replace = '.') {
			return substr($string, 0, $visible) . str_repeat($replace, strlen($string) - $visible);
		}

		if ($request->method() === 'POST') {
			$start_date = new Carbon($request->start, Auth::user()->timezone);
			$end_date = (new Carbon($request->end, Auth::user()->timezone))->subSecond()->addDay();
		} else {
			$start_date = new Carbon($start, Auth::user()->timezone);
			$end_date = (new Carbon($end, Auth::user()->timezone))->subSecond()->addDay();
		}

		$start_date->setTimezone('UTC');
		$end_date->setTimezone('UTC');

		$invoices = DB::table('invoices')->select([
			"invoices.created_at",
			"invoices.serial",
			"invoices.active",
			"invoices.name",
			DB::raw('CONCAT(patients.code, " - ", patients.lastname, ", ", patients.firstname) AS patient'),
			DB::raw('SUM(sessions.amount) AS total'),
		])
			->where("invoices.user_id", "=", Auth::user()->id)
			->whereBetween("invoices.created_at", [$start_date, $end_date])
			->join("patients", "patients.id", "=", "invoices.patient_id")
			// We use left join in case that there has been an interruption
			// between storing the invoice and storing it's sessions,
			// which the result is an invoice with no sessions.
			->leftJoin("sessions", "sessions.invoice_id", "=", "invoices.id")
			->groupBy("created_at", "serial", "active", "name", "patient")
			->get();

		$currency_params = app('DEFAULT_CURRENCY_PARAMS');
		$currency_params['grouping_used'] = true;

		$total = 0;
		foreach ($invoices as $invoice) {
			if ($invoice->active) {
				$total += $invoice->total;
			} else {
				$invoice->total = 0;
			}

			$invoice->name = hide_info($invoice->name, 3);
			$invoice->patient = hide_info($invoice->patient, 4);
			$invoice->date = Carbon::parse($invoice->created_at)
				->timezone(Auth::user()->timezone)
				->format('d/m/Y');
			$invoice->total_float = $invoice->total / 100;
			$invoice->total = currency_format($invoice->total, true, $currency_params);
			$invoice->reference = $this->generateReference($invoice);
		}

		$total_float = $total / 100;
		$total = currency_format($total, true, $currency_params);

		if ($request->method() === 'POST') {
			$start = Carbon::parse($request->start)->format('d/m/Y');
			$end = Carbon::parse($request->end)->format('d/m/Y');

			// return view('invoice-report', compact('start', 'end', 'invoices', 'total'));
			return response()->json([
				'success' => true,
				'data' => view('invoice-report', compact('start', 'end', 'invoices', 'total'))->render(),
			]);
		}

		$start = Carbon::parse($start)->format('d/m/Y');
		$end = Carbon::parse($end)->format('d/m/Y');
		$filename = "report_" . (str_replace("/", "-", $start)) . "_" . (str_replace("/", "-", $end)) . ".xlsx";

		$export = new InvoiceExportView(
			$start,
			$end,
			$invoices,
			$total_float
		);

		return Excel::download($export, $filename);
	}
}
