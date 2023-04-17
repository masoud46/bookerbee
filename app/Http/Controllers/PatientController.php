<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

		$patients_count = Patient::all()->count();

		return view('patient-index', [
			'entries' => $entries,
			'patients_count' => $patients_count,
		]);
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
			$patient = Patient::find($form_key['patient_id']);
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
			// 'patient-code' => ["required", "regex:/^(?=\d*$)(?:.{13}|.{15})$/", "unique:patients,code" . ($is_update ? ",{$form_key['patient_id']}" : "")],
			'patient-code' => [$code_regex, "unique:patients,code" . ($is_update ? ",{$form_key['patient_id']}" : "")],
			'patient-firstname' => "required",
			'patient-lastname' => "required",
			'patient-email' => "nullable|email|unique:patients,email" . ($is_update ? ",{$form_key['patient_id']}" : ""),
			'patient-phone_country_id' => "required|numeric",
			'patient-phone_number' => "nullable",
			'patient-address_line1' => "required",
			'patient-address_code' => "required",
			'patient-address_city' => "required",
			'patient-address_country_id' => "required|numeric",
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
		];

		$validator = Validator::make($params, $params_rules, $params_messages);

		if ($validator->fails()) {
			session()->flash("error", app('ERRORS')['form']);
			return back()->withErrors($validator->errors())->withInput();
		}

		if (!$is_update) { // create request
			$patient = new Patient();
		}

		if (!$patient_object['phone_number']) {
			$patient_object['phone_country_id'] = null;
		}

		foreach ($patient_object as $key => $value) {
			$patient[$key] = $value;
		}
		$patient->firstname = ucfirst($patient->firstname);
		$patient->lastname = strtoupper($patient->lastname);
		$patient->save();

		if ($is_update) {
			session()->flash("success", __("Patient data has been updated."));
			return back()->withInput();
		}
		
		session()->flash("success", __("The new patient has been created."));
		return redirect()->route("patient.show", [
			'patient' => $patient->id,
		]);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  \App\Models\Patient  $patient
	 * @return \Illuminate\Http\Response
	 */
	public function show(Patient $patient) {
		$entries = 'resources/js/pages/patient.js';

		$countries = Country::sortedList();

		$patient_object = [
			'entries' => $entries,
			'countries' => $countries,
		];

		if (count($patient->toArray())) {
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
			DB::raw("UPPER(patients.lastname) AS lastname"),
		])
			->where("code", "LIKE", "{$request->str}%")
			->orWhere("lastname", "LIKE", "%{$request->str}%")
			->orWhere("firstname", "LIKE", "%{$request->str}%")
			// had to use the following approach to eliminate duplicates
			// where $request->str found in more than one orWhere
			// ->where(function ($query) use ($request) {
			// 	$query
			// 		->where("code", "LIKE", "{$request->str}%")
			// 		->orWhere("lastname", "LIKE", "%{$request->str}%")
			// 		->orWhere("firstname", "LIKE", "%{$request->str}%");
			// })
			->orderBy("code")
			->get();

		return response()->json($patients);
	}

}
