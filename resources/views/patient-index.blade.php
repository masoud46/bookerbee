@extends('layouts.app', ['page_title' => '<i class="fas fa-user me-2"></i>' . __('Patient')])

@php
	$default_country_code = config('project.default_country_code');
@endphp

@section('content')
	<div class="container">
		<div id="patient-picker" class="row my-4">
			<div class="col-md-8 col-lg-6">
				<div class="text-end pe-2"><small><span class="patient-count me-1">0</span> / {{ $patients_count }}</small></div>
				<x-patient-picker id="patient-picker-component" autocomplete-url="{{ route('patient.autocomplete') }}" picked-url="{{ route('patient.show', ['patient' => '?id']) }}" placeholder="{{ __('Last name / First name / Reg. number') }}" helper-text="{{ __('Start by typing three characters.') }}" />
			</div>
			<div class="col-md-4 col-lg-6">
				<div class="invisible">[placeholder]</div>
				<a type="button" class="btn btn-success btn-add-patient" href="{{ route('patient.new') }}"><i class="fas fa-user-plus fa-fw"></i> {{ __('Add a patient') }}</a>
			</div>
		</div>
		<div id="patients-container" class="row my-4">
			<input type="hidden" id="patient-show-url" value="{{ route('patient.show', ['patient' => '?id']) }}">
			<div class="col-12">
				<div class="accordion" id="accordionFlushExample">
					<div class="accordion-item">
						<h2 class="accordion-header" id="flush-headingOne">
							<button class="accordion-button collapsed bg-secondary bg-opacity-10 px-3 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
								<h4 class="m-0">{{ __('All my patients') }}</h4>
							</button>
						</h2>
						<div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
							<div class="accordion-body d-flex flex-column position-relative">
								<div id="items-table-filter" class="row">
									<div class="col-md-6 col-lg-7 col-xl-8 mt-2">
										<button type="button" class="btn btn-sm btn-secondary"><i class="fas fa-arrows-rotate fa-fw me-1"></i>{{ __("Refresh") }}</button>
										<small class="h-100 d-flex align-items-center text-nowrap float-end"><span class="items-table-count me-1">0</span>/<span class="items-table-total ms-1">0</span></small>
									</div>
									<div class="col-md-6 col-lg-5 col-xl-4 mt-3 mt-sm-2 d-flex">
										<input class="items-table-filter-input form-control" placeholder="{{ __('Search') }}" value="">
									</div>
								</div>
								<div class="table-responsive mt-2">
									<table class="table table-sm table-striped table-hover">
										<thead>
											<tr>
												<th scope="col">{{ __('Reg. number') }}</th>
												<th scope="col">{{ __('Last name') }}</th>
												<th scope="col">{{ __('First name') }}</th>
												<th scope="col">{{ __('Email address') }}</th>
												<th scope="col">{{ __('Phone') }}</th>
											</tr>
										</thead>
										<tbody></tbody>
									</table>
								</div>
								<div class="loading-overlay position-absolute top-0 start-0 bottom-0 end-0 d-flex justify-content-center align-items-center">
									<div class="spinner-border text-secondary opacity-50" role="status">
										<span class="visually-hidden">{{ __('Loading...') }}</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@push('assets')
	@vite($entries)
@endpush
