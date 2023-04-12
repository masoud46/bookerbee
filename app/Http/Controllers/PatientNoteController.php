<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientNotes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class PatientNoteController extends Controller {
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
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request) {
		$result = ['success' => false];

		$params = $request->all();

		try {
			$form_key = Crypt::decrypt($params['form-key']);
			$notes = PatientNotes::select(['id', 'notes'])
				->where('patient_id', $form_key['patient_id'])
				->where('user_id', Auth::user()->id)
				// ->latest("created_at")
				->first();

				$result['success'] = true;
				$result['data'] = $notes ? nl2br($notes->notes) : null;
		} catch (\Throwable $th) {
		}

		return response()->json($result);
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
		$result = ['success' => false];

		$params = $request->all();

		if (!isset($params['form-key'])) {
			$result['error'] = [
				'code' => 400,
				'message' => __("Form key not found."),
			];

			return response()->json($result);
		}

		try {
			$form_key = Crypt::decrypt($params['form-key']);
		} catch (\Throwable $th) {
			$result['error'] = [
				'code' => 400,
				'message' => __("Form key is corrupted."),
			];

			return response()->json($result);
		}

		if (!is_array($form_key) || !isset($form_key['patient_id'])) {
			$result['error'] = [
				'code' => 400,
				'message' => __("Form key is invalid."),
			];

			return response()->json($result);
		}

		$notes = $params['notes'];

		$notes_str = "";
		foreach ($notes as $value) {
			$notes_str .= $value . "\n";
		}
		$notes_str = trim($notes_str);

		try {
			$notes = PatientNotes::updateOrCreate(
				['patient_id' => $form_key['patient_id'], 'user_id' => Auth::user()->id],
				['notes' => $notes_str]
			);

			$result['success'] = true;
			$result['data'] = nl2br($notes_str);
		} catch (\Throwable $th) {
			//throw $th;
		}

		return response()->json($result);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  \App\Models\PatientNotes  $patientNotes
	 * @return \Illuminate\Http\Response
	 */
	public function show(PatientNotes $patientNotes) {
		//
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  \App\Models\PatientNotes  $patientNotes
	 * @return \Illuminate\Http\Response
	 */
	public function edit(PatientNotes $patientNotes) {
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Models\PatientNotes  $patientNotes
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, PatientNotes $patientNotes) {
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Models\PatientNotes  $patientNotes
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(PatientNotes $patientNotes) {
		//
	}
}
