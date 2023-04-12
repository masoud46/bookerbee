@extends('layouts.app', ['page_title' => '<i class="fas fa-user me-2"></i>' . __("Patient")])

@php($default_country_code = config('project.default_country_code'))

@section('content')
	<div class="container">
		{{-- <div class="row mt-4">
			<div class="col-md-8 col-lg-6 pt-4">
				<div class="patient-count"><span></span>{{ $patients_count }}</div>
			</div>
		</div> --}}
		<div id="patient-picker" class="row my-4">
			<div class="col-md-8 col-lg-6 pt-4">
				<div class="text-end pe-2"><small><span class="patient-count me-1">0</span> / {{ $patients_count }}</small></div>
				<x-patient-picker id="patient-picker-component" autocomplete-url="{{ route('patient.autocomplete') }}" picked-url="{{ route('patient.show', ['patient' => '?id']) }}" placeholder="{{ __('Last name / First name / Reg. number') }}" helper-text="{{ __('Start by typing three characters.') }}" />
			</div>
			<div class="col-md-4 col-lg-6 pt-4">
				<div class="invisible">[placeholder]</div>
				<a type="button" class="btn btn-success btn-add-patient" href="{{ route('patient.new') }}"><i class="fas fa-user-plus fa-fw"></i> {{ __("Add a patient") }}</a>
			</div>
		</div>
	</div>
@endsection

@push('assets')
	@vite($entries)
@endpush
