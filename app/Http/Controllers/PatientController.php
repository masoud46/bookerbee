<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Patient;
use Hashids;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PatientController extends Controller {
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
		$entries = 'resources/js/pages/index.js';

		$patients_count = Patient::whereUserId(Auth::user()->id)->count();

		return view('patient-index', compact(
			'entries',
			'patients_count',
		));
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create() {
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {
		$params = $request->all();

		$form_key = [];
		try {
			$form_key = Crypt::decrypt($params['form-key']);
		} catch (\Throwable $th) {
		}
		unset($params['form-key']);

		$patient = null;
		$is_update = isset($form_key['patient_id']);

		if ($is_update) { // update request
			$patient = Patient::whereId($form_key['patient_id'])
				->whereUserId(Auth::user()->id)
				->first();
			if ($patient === null) {
				session()->flash("error", __("Patient not found."));
				return back()->withInput();
			}
		}

		$patient_object =  [];

		foreach ($params as $key => $value) {
			$field = explode("-", $key, 2);
			switch ($field[0]) {
				case "patient":
					$patient_object[$field[1]] = $value;
					break;
			}
		}

		$patient_object['category'] = isset($patient_object['category']) ? 1 : 2;
		$code_regex = $patient_object['category'] === 1 // National healthcare
			? "regex:/^(?=\d*$)(?:.{13}|.{15})$/"
			: "regex:/^\d{8,15}$/";

		$params_rules = [
			'patient-code' => [
				"required",
				$code_regex,
				Rule::unique('patients', 'code')
					->where('user_id', Auth::user()->id)
					->ignore($form_key['patient_id'] ?? -1),
			],
			'patient-firstname' => "required",
			'patient-lastname' => "required",
			'patient-email' => [
				"nullable",
				"email",
				Rule::unique('patients', 'email')
					->where('user_id', Auth::user()->id)
					->ignore($form_key['patient_id'] ?? -1),
			],
			'patient-phone_country_id' => "nullable|numeric",
			'patient-phone_number' => "nullable",
			'patient-address_line1' => "required",
			'patient-address_code' => "required",
			'patient-address_city' => "required",
			'patient-address_country_id' => "required|numeric",
			'patient-locale' => "required",
		];

		$params_messages = [
			'patient-code.required' => app('ERRORS')['required'],
			'patient-code.regex' => app('ERRORS')['regex']['code'],
			'patient-code.unique' => app('ERRORS')['unique']['code'],
			'patient-firstname.required' => app('ERRORS')['required'],
			'patient-lastname.required' => app('ERRORS')['required'],
			'patient-email.email' => app('ERRORS')['email'],
			'patient-email.unique' => app('ERRORS')['unique']['email'],
			'patient-phone_country_id.numeric' => app('ERRORS')['numeric'],
			'patient-address_line1.required' => app('ERRORS')['required'],
			'patient-address_code.required' => app('ERRORS')['required'],
			'patient-address_city.required' => app('ERRORS')['required'],
			'patient-address_country_id.required' => app('ERRORS')['required'],
			'patient-address_country_id.numeric' => app('ERRORS')['numeric'],
			'patient-locale.required' => app('ERRORS')['required'],
		];

		$validator = Validator::make($params, $params_rules, $params_messages);

		if ($validator->fails()) {
			session()->flash("error", app('ERRORS')['form']);
			return back()->withErrors($validator->errors())->withInput();
		}

		if (!$is_update) { // create request
			$patient = new Patient();
			$patient->user_id = Auth::user()->id;
		}

		if (!$patient_object['phone_number']) {
			$patient_object['phone_country_id'] = null;
		}

		foreach ($patient_object as $key => $value) {
			$patient[$key] = $value;
		}

		$patient->firstname = ucfirst($patient->firstname);
		$patient->lastname = ucfirst($patient->lastname);
		$patient->save();

		if ($is_update) {
			session()->flash("success", __("Patient data has been updated."));
		} else {
			session()->flash("success", __("The new patient has been created."));
		}
		
		return redirect()->route("patient.index");
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  \App\Models\Patient  $patient
	 * @return \Illuminate\Http\Response
	 */
	public function show(Patient $patient = null) {
		if ($patient && $patient->user_id !== Auth::user()->id) {
			abort(404);
		}

		$entries = 'resources/js/pages/patient.js';

		$countries = Country::sortedList();

		$patient_object = [
			'entries' => $entries,
			'countries' => $countries,
		];

		if ($patient) { // update request
			if ($patient->phone_country_id) {
				$patient->phone_prefix = Country::find($patient->phone_country_id)->prefix;
			}

			$key = Crypt::encrypt([
				'patient_id' => $patient->id,
			]);

			$patient_object = array_merge($patient_object, [
				'key' => $key,
				'patient' => $patient,
			]);
		}

		return view('patient', $patient_object);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  \App\Models\Patient  $patient
	 * @return \Illuminate\Http\Response
	 */
	public function edit(Patient $patient) {
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Models\Patient  $patient
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, Patient $patient) {
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Models\Patient  $patient
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Patient $patient) {
		//
	}

	/**
	 * Get patients list with matched pattern based on code, last name and first name.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function autocomplete(Request $request) {
		$patients = Patient::select([
			"id",
			"category",
			"code",
			"firstname",
			"lastname",
			"email",
			"phone_number",
			"phone_country_id",
			"locale",
		])
			->whereUserId(Auth::user()->id)
			->where(function ($query) use ($request) {
				$query
					->where("code", "LIKE", "{$request->str}%")
					->orWhere("lastname", "LIKE", "%{$request->str}%")
					->orWhere("firstname", "LIKE", "%{$request->str}%");
			})
			->orderBy("code")
			->get();
		
		// foreach ($patients as $key => $value) {
		// 	$value->hash = Hashids::encode($value->id);
		// }

		return response()->json($patients);
	}


	/**
	 * Get all the patients
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function list() {
		$prefixes = array_column(Country::all()->toArray(), 'prefix', 'id');
		$patients = Patient::select([
			"id",
			"category",
			"code",
			"firstname",
			"lastname",
			"email",
			"phone_country_id",
			"phone_number",
		])
			->whereUserId(Auth::user()->id)
			->orderBy("code")
			->get();

		foreach ($patients as $patient) {
			if ($patient->phone_country_id) {
				$patient->phone_prefix = $prefixes[$patient->phone_country_id];
			}
			unset($patient->phone_country_id);
		}

		return response()->json($patients);
	}

}
