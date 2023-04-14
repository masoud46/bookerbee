@extends('layouts.app', ['page_title' => '<i class="fas fa-rectangle-list me-2"></i>' . __("Statement")])

@php
	// var_dump(currency_format('112230'));
	// var_dump(currency_parse('1212,30'));
	// var_dump(currency_parse('1212,30 €', ['show_symbol' => true]));
	
	$locations_tooltip = '';
	foreach ($locations as $location) {
	    $locations_tooltip .= "<div>{$location->code} : {$location->description}</div>";
	}
	
	$types_tooltip = '';
	foreach ($types as $type) {
	    $types_tooltip .= "<div>{$type->code} : {$type->description}</div>";
	}
	
	$default_country_code = config('project.default_country_code');
	$add = isset($key) && isset($patient);
	$update = isset($invoice) && isset($appointments);
	$editable = $add || $invoice->editable;
	$disabled = $editable ? '' : 'disabled';
	
	$category = $add ? $patient->category : $invoice->patient_category;
	$sessions = $add ? $patient->sessions : $invoice->patient_sessions;
	$appDefaultLocation = 3;
	$last_app = -1;
	$title_color = $category === 1 ? 'national-healthcare' : 'secondary';
	
	if ($update) {
	    $last_app = $appointments->count() - 1;
	}
@endphp

@section('content')
	<div class="container">
		<form id="invoice-form" method="post" action="{{ route('invoice.store') }}" class="form" autocomplete="off" autofill="off">

			{{-- @if ($update)
					@method('put')
				@endif --}}

			@csrf

			<input type="hidden" id="patient-email" value="{{ $update ? $invoice->patient_email : $patient->email }}">
			<input type="hidden" id="patient-notes-fetch-url" value="{{ route('patient.notes') }}">
			<input type="hidden" id="patient-notes-store-url" value="{{ route('patient.notes.store') }}">
			<input type="hidden" id="form-key" name="form-key" value="{{ $key }}">
			<input type="hidden" id="invoice-saved" value="true">
			<input type="hidden" id="invoice-sessions-types" value='{{ json_encode($types->toArray()) }}'>
			<input type="hidden" id="invoice-patient-category" value='{{ $category }}'>
			<div class="row">
				<div class="col-md-6">
					<h6 class="rounded-1 bg-{{ $title_color }} text-white mt-4 py-1 px-3">{{ __("Patient") }} {!! $category === 1 ? '- CNS' : '' !!}
						<span id="patient-notes" class="float-end" data-bs-toggle="modal" data-bs-target="#patient-notes-modal">
							<span data-bs-toggle="tooltip" data-bs-html="true" data-bs-custom-class="app-tooltip" data-bs-title="{{ __("Notes") }}">
								<i class="far fa-file-lines"></i>
							</span>
						</span>
					</h6>
					<div class="mb-3 row">
						<label for="patient-code" class="col-sm-4 col-form-label col-form-label-sm text-sm-end">{{ __("Registration number") }}</label>
						<div class="col-sm-8">
							<input id="patient-code" class="form-control-plaintext form-control-sm ps-2" readonly value="{{ old('patient-code', $update ? $invoice->patient_code : $patient->code) }}">
						</div>
					</div>
					<div class="mb-3 row">
						<label for="invoice-name" class="col-sm-4 col-form-label col-form-label-sm text-sm-end"><span class="required-field">{{ __("Full name") }}</span></label>
						<div class="col-sm-8">
							<input id="invoice-name" name="invoice-name" class="form-control form-control-sm @error('invoice-name') is-invalid @enderror" value="{{ old('invoice-name', $update ? $invoice->name : $patient->name) }}">
							@error('invoice-name')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="invoice-acc_number" class="col-sm-4 col-form-label col-form-label-sm text-sm-end">{{ __("Incident N°") }}</label>
						<div class="col-sm-8">
							<input id="invoice-acc_number" name="invoice-acc_number" class="form-control form-control-sm" value="{{ old('invoice-acc_number', $update ? $invoice->acc_number : '') }}">
						</div>
					</div>
					<div class="mb-3 row">
						<label for="invoice-acc_date" class="col-sm-4 col-form-label col-form-label-sm text-sm-end">{{ __("Incident date") }}</label>
						<div class="col-sm-8">
							<x-resetable-date data-small="true" class="resetable-date-container form-control-sm" inputId="invoice-acc_date" inputName="invoice-acc_date" inputValue="{{ old('invoice-acc_date', $update ? $invoice->acc_date : '') }}" />
						</div>
					</div>
					<h6 class="rounded-1 bg-{{ $title_color }} text-white mt-4 py-1 px-3">{{ __("Prescription") }}</h6>
					<div class="mb-3 row">
						<label for="invoice-doc_code" class="col-sm-4 col-form-label col-form-label-sm text-sm-end"><span class="required-field">{{ __("Prescriber") }}</span></label>
						<div class="col-sm-8 mb-1">
							<input id="invoice-doc_code" name="invoice-doc_code" class="form-control form-control-sm mb-1 @error('invoice-doc_code') is-invalid @enderror" value="{{ old('invoice-doc_code', $update ? $invoice->doc_code : '') }}" placeholder="{{ __("Code") }}">
							@error('invoice-doc_code')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
							<input id="invoice-doc_name" name="invoice-doc_name" class="form-control form-control-sm" value="{{ old('invoice-doc_name', $update ? $invoice->doc_name : '') }}" placeholder="{{ __("Name") }}">
						</div>
					</div>
					<div class="mb-3 row">
						<label for="invoice-doc_date" class="col-sm-4 col-form-label col-form-label-sm text-sm-end"><span class="required-field">{{ __("Date") }}</span></label>
						<div class="col-sm-8">
							<input type="date" id="invoice-doc_date" name="invoice-doc_date" class="form-control form-control-sm @error('invoice-doc_date') is-invalid @enderror" value="{{ old('invoice-doc_date', $update ? $invoice->doc_date : '') }}">
							@error('invoice-doc_date')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
				</div>
				<div class="col-md-6">
					<h6 class="rounded-1 bg-{{ $title_color }} text-white mt-4 py-1 px-3">{{ __("Covered patient") }}</h6>
					<div class="mb-3 row">
						<label for="patient-lastname" class="col-sm-4 col-form-label col-form-label-sm text-sm-end"><span class="required-field">{{ __("Last name") }}</span></label>
						<div class="col-sm-8">
							<input id="patient-lastname" name="patient-lastname" class="form-control form-control-sm @error('patient-lastname') is-invalid @enderror" value="{{ old('patient-lastname', $update ? $invoice->patient_lastname : $patient->lastname) }}">
							@error('patient-lastname')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="patient-firstname" class="col-sm-4 col-form-label col-form-label-sm text-sm-end"><span class="required-field">{{ __("First name") }}</span></label>
						<div class="col-sm-8">
							<input id="patient-firstname" name="patient-firstname" class="form-control form-control-sm @error('patient-firstname') is-invalid @enderror" value="{{ old('patient-firstname', $update ? $invoice->patient_firstname : $patient->firstname) }}">
							@error('patient-firstname')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="row">
						<label for="patient-address_line1" class="col-sm-4 col-form-label col-form-label-sm text-sm-end"><span class="required-field">{{ __("Address") }}</span></label>
						<div class="col-sm-8">
							<div class="mb-1">
								<input id="patient-address_line1" name="patient-address_line1" class="form-control form-control-sm @error('patient-address_line1') is-invalid @enderror" placeholder="{{ __("Line :line", ['line' => 1]) }}" value="{{ old('patient-address_line1', $update ? $invoice->patient_address_line1 : $patient->address_line1) }}">
								@error('patient-address_line1')
									<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
							<div class="mb-1">
								<input id="patient-address_line2" name="patient-address_line2" class="form-control form-control-sm" placeholder="{{ __("Line :line", ['line' => 2]) }}" value="{{ old('patient-address_line2', $update ? $invoice->patient_address_line2 : $patient->address_line2) }}">
							</div>
							<div class="mb-3">
								<input id="patient-address_line3" name="patient-address_line3" class="form-control form-control-sm" placeholder="{{ __("Line :line", ['line' => 3]) }}" value="{{ old('patient-address_line3', $update ? $invoice->patient_address_line3 : $patient->address_line3) }}">
							</div>
							<div class="row">
								<div class="mb-3 col-lg-5">
									<input id="patient-address_code" name="patient-address_code" class="form-control form-control-sm @error('patient-address_code') is-invalid @enderror" placeholder="{{ __("Postal code") }}" value="{{ old('patient-address_code', $update ? $invoice->patient_address_code : $patient->address_code) }}">
									@error('patient-address_code')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
								<div class="mb-3 col-lg-7">
									<input id="patient-address_city" name="patient-address_city" class="form-control form-control-sm @error('patient-address_city') is-invalid @enderror" placeholder="{{ __("City") }}" value="{{ old('patient-address_city', $update ? $invoice->patient_address_city : $patient->address_city) }}">
									@error('patient-address_city')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
							</div>
							<div class="mb-3">
								<select id="patient-address_country_id" name="patient-address_country_id" class="form-select form-select-sm @error('patient-address_country_id') is-invalid @enderror">
									<option value="" selected hidden>{{ __("Country") }}</option>
									@foreach ($countries as $country)
										@php($selected = $add ? $country->id === $patient->address_country_id : ($update ? $country->id === $invoice->patient_address_country_id : $country->code === $default_country_code))
										@if (old('patient-address_country_id'))
											{{ $selected = intval(old('patient-address_country_id')) === $country->id }}
										@endif
										<option value="{{ $country->id }}"{{ $selected ? ' selected' : '' }}>{{ $country->name }}</option>
									@endforeach
								</select>
								@error('patient-address_country_id')
									<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
						</div>
					</div>
				</div>
				<div class="col-12">
					<div class="border-top pt-1"><small class="text-muted fst-italic">{!! __("Fields indicated by :star are required.", ['star' => '<span class="required-field me-2"></span>&nbsp;']) !!}</small></div>
				</div>
			</div>
			<div class="row my-2">
				<div class="col-12">
					<h6 class="text-{{ $title_color }} text-center mt-2 mb-0 pt-2 fw-bold">
						{{ __("INVOICE") }} {!! $category === 1 ? '- CNS' : '' !!}
					</h6>
					<h6 class="text-{{ $title_color }} border-bottom text-center mt-2 mb-0 pb-2">
						{{ __("Previous sessions:") }} <span id="patient-prev-sessions" class="fw-bold">{{ $sessions }}</span>
					</h6>
				</div>
			</div>
			<div class="row">
				<div class="col-1 col-md-5 col-lg-4 d-flex align-items-center">
					<span class="text-secondary text-opacity-50" data-bs-toggle="tooltip" data-bs-html="true" data-bs-custom-class="app-tooltip" data-bs-title="{!! $locations_tooltip !!}"><i class="far fa-circle-question"></i></span>
				</div>
				<div class="col-11 col-md-7 col-lg-8 d-flex align-items-center">
					<span class="text-secondary text-opacity-50" data-bs-toggle="tooltip" data-bs-html="true" data-bs-custom-class="app-tooltip" data-bs-title="{!! $types_tooltip !!}"><i class="far fa-circle-question"></i></span>
				</div>
			</div>
			<div id="invoice-apps" class="row">
				@for ($i = 0; $i < 10; $i++)
					@php($app = $i <= $last_app ? $appointments[$i] : null)
					@php($hidden = $i === 0 || $i <= $last_app ? '' : 'd-none')
					<div class="col-12 app-item {{ $hidden }}">
						<input type="hidden" name="app-visible-{{ $i }}" value="{{ $hidden ? '' : 'visible' }}">
						<div class="row">
							<div class="col-md-2 mb-1">
								<select name="app-location_id-{{ $i }}" class="app-location form-select form-select-sm @error("app-location_id-{$i}") is-invalid @enderror" default-value={{ $appDefaultLocation }}>
									<option value="" selected hidden>{{ __("Location") }}</option>
									@foreach ($locations as $location)
										@php($selected = $app ? ($app->location_id === $location->id ? 'selected' : '') : ($location->id === $settings->location ? ' selected' : ''))
										@if (old("app-location_id-{$i}"))
											{{ $selected = intval(old("app-location_id-{$i}")) === $location->id ? 'selected' : '' }}
										@endif
										<option value="{{ $location->id }}" {{ $selected }} {{ $location->disabled ? 'disabled' : '' }}>{{ $location->code }}</option>
									@endforeach
								</select>
								@error("app-location_id-{$i}")
									<div class="invalid-feedback"></div>
								@enderror
							</div>
							<div class="col-md-3 col-lg-2 mb-1">
								<input type="date" name="app-done_at-{{ $i }}" class="app-date form-control form-control-sm @error("app-done_at-{$i}") is-invalid @enderror" placeholder="Date" value="{{ old("app-done_at-{$i}", $app && $app->done_at ? $app->done_at : '') }}">
								@error("app-done_at-{$i}")
									<div class="invalid-feedback"></div>
								@enderror
							</div>
							<div class="col-md-2 mb-1 app-type-wrapper" data-session="{{ $hidden ? '0' : $sessions + $i + 1 }}">
								<select name="app-type_id-{{ $i }}" class="app-type form-select form-select-sm @error("app-type_id-{$i}") is-invalid @enderror">
									<option value="" selected hidden>{{ __("Acte") }}</option>
									@php($type_id = null)
									@php($description = null)
									@foreach ($types as $type)
										<?php
										if ($type_id === null) {
										    if ($app) {
										        if ($app->type_id === $type->id) {
										            $type_id = $type->id;
										            // $description = $category === 1 ? $type->description : $app->description;
										        }
										    } else {
										        $type_id = $type->max_sessions ? ($sessions + 1 > $type->max_sessions ? null : $type->id) : $type->id;
										        if ($description === null && $category === 1 && $type_id) {
										            $description = $type->description;
										        }
										    }
										}
										?>
										@php($selected = $type->id === $type_id ? 'selected' : '')
										@if (old("app-type_id-{$i}"))
											{{ $selected = intval(old("app-type_id-{$i}")) === $type->id ? 'selected' : '' }}
										@endif
										<option value="{{ $type->id }}" {{ $selected }} data-description="{{ $type->description }}">{{ $type->code }}</option>
									@endforeach
								</select>
								@error("app-type_id-{$i}")
									<div class="invalid-feedback"></div>
								@enderror
							</div>
							<div class="col-md-3 col-lg-4 mb-1">
								<input name="app-description-{{ $i }}" class="app-description form-control form-control-sm @error("app-description-{$i}") is-invalid @enderror" placeholder="{{ __("Description") }}" {{ $category === 1 ? 'disabled' : '' }} value="{{ old("app-description-{$i}", $app && $app->description ? $app->description : $description) }}">
								@error("app-description-{$i}")
									<div class="invalid-feedback"></div>
								@enderror
							</div>
							<div class="col-md-2 mb-1 d-flex">
								<div class="input-group input-group-sm flex-grow-1 @error("app-amount-{$i}") has-validation @enderror">
									<input name="app-amount-{{ $i }}" class="xapp-amount form-control form-control-sm @error("app-amount-{$i}") is-invalid @enderror" aria-describedby="input-group-sizing-sm" placeholder="{{ __("Amount") }}" default-value="{{ $settings->amount }}" value="{{ old("app-amount-{$i}", $app && $app->amount ? $app->amount : $settings->amount) }}">
									<span class="input-group-text" id="input-group-sizing-sm">€</span>
									@error("app-amount-{$i}")
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
								@if ($editable)
									<div class="remove-app-container">
										@if ($i > 0)
											<div class="remove-app text-danger">
												<i class="fas fa-trash-can"></i>
											</div>
										@endif
									</div>
								@endif
							</div>
							{{-- <div class="col-md-2 mb-1">
								<div class="input-group input-group-sm @error("app-insurance-{$i}") has-validation @enderror">
									<input name="app-insurance-{{ $i }}" class="app-insurance form-control form-control-sm @error("app-insurance-{$i}") is-invalid @enderror" aria-describedby="input-group-sizing-sm" placeholder="{{ __("Coverd-part") }}" value="{{ $app && $app->insurance ? $app->insurance : '' }}">
									<span class="input-group-text" id="input-group-sizing-sm">€</span>
									@error("app-insurance-{$i}")
										<div class="invalid-feedback"></div>
									@enderror
								</div> --}}
						</div>
					</div>
				@endfor
			</div>
			<div class="mb-3 row">
				@if ($editable)
					<div class="col-12">
						<a id="add-app" class="btn btn-sm btn-success"><i class="fas fa-plus fa-fw"></i> {{ __("Add") }}</a>
					</div>
				@endif
			</div>
			<div class="mb-3 row">
				<div class="col-8 col-sm-5 col-md-4 col-lg-3">
					<label for="invoice-granted_at" class="w-100 col-form-label col-form-label-sm">{{ __("For acquired on") }}</label>
					<div class="w-100">
						<x-resetable-date class="resetable-date-container form-control-sm" inputId="invoice-granted_at" inputName="invoice-granted_at" inputValue="{{ old('invoice-granted_at', $update ? $invoice->granted_at : '') }}" />
						@error('invoice-granted_at')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-4 col-sm-3 col-md-2">
					<div class="row">
						<label for="invoice-prepayment" class="w-100 col-form-label col-form-label-sm">{{ __("Prepayment") }}</label>
						<div class="w-100 input-group input-group-sm @error('invoice-prepayment') has-validation @enderror">
							<input id="invoice-prepayment" name="invoice-prepayment" class="form-control form-control-sm @error('invoice-prepayment') is-invalid @enderror" value="{{ old('invoice-prepayment', $update ? $invoice->prepayment : '') }}">
							<span class="input-group-text" id="input-group-sizing-sm">€</span>
							@error('invoice-prepayment')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
				</div>
			</div>
			<div class="row mt-4">
				@php($location_checked = old('invoice-location_check', $update && $invoice->location_check))
				<div class="col-12">
					<div class="form-check form-switch">
						{{-- <input id="invoice-location_check" name="invoice-location_check" type="checkbox" role="switch" class="form-check-input" {{ $update ? ($invoice->location_check ? 'checked' : '') : '' }}> --}}
						<input id="invoice-location_check" name="invoice-location_check" type="checkbox" role="switch" class="form-check-input" @checked($location_checked)>
						<label class="form-check-label" for="invoice-location_check">{{ __("Location different from cabinet") }}</label>
					</div>
				</div>
				<div id="invoice-location" class="col-12{{ $location_checked ? ' location-visible' : '' }}">
					<div class="@error('invoice-location') is-invalid @enderror">
						<div class="row" name="invoice-location">
							<div class="col-sm-8 col-md-6 col-lg-5 my-1">
								<input id="invoice-location_name" name="invoice-location_name" class="form-control form-control-sm @error('invoice-location_name') is-invalid @enderror" placeholder="{{ __("Location name") }}" value="{{ old('invoice-location_name', $update ? $invoice->location_name : '') }}">
							</div>
						</div>
						<div class="row">
							<div class="col-md-10 col-lg-8 mb-1">
								<input id="invoice-location_address" name="invoice-location_address" class="form-control form-control-sm @error('invoice-location_address') is-invalid @enderror" placeholder="{{ __("Address") }}" value="{{ old('invoice-location_address', $update ? $invoice->location_address : '') }}">
							</div>
						</div>
						<div class="row">
							<div class="col-sm-3 col-md-2 mb-1">
								<input id="invoice-location_code" name="invoice-location_code" class="form-control form-control-sm @error('invoice-location_code') is-invalid @enderror" placeholder="{{ __("Postal code") }}" value="{{ old('invoice-location_code', $update ? $invoice->location_code : '') }}">
							</div>
							<div class="col-sm-5 col-md-4 col-lg-3 mb-1">
								<input id="invoice-location_city" name="invoice-location_city" class="form-control form-control-sm @error('invoice-location_city') is-invalid @enderror" placeholder="{{ __("City") }}" value="{{ old('invoice-location_city', $update ? $invoice->location_city : '') }}">
							</div>
							<div class="col-sm-4 col-md-4 col-lg-3 mb-1">
								<select id="invoice-location_country_id" name="invoice-location_country_id" class="form-select form-select-sm @error('invoice-location_country_id') is-invalid @enderror">
									<option value="" selected hidden>{{ __("Country") }}</option>
									@foreach ($countries as $country)
										@php($selected = $update && $invoice->location_check ? $country->id === $invoice->location_country_id : $country->code === $default_country_code)
										@if (old('invoice-location_country_id'))
											{{ $selected = intval(old('invoice-location_country_id')) === $country->id }}
										@endif
										<option value="{{ $country->id }}"{{ $selected ? ' selected' : '' }}>{{ $country->name }}</option>
									@endforeach
								</select>
							</div>
						</div>
					</div>
					@error('invoice-location')
						<div class="invalid-feedback">{{ $message }}</div>
					@enderror
				</div>
			</div>
			<div class="row my-4">
				<div class="col-12">
					<button type="submit" id="save-invoice" class="btn btn-primary btn-fa-spinner me-3" data-saved="0"><i class="icon-visible fas fa-file-arrow-down fa-fw"></i><i class="icon-hidden fas fa-spinner fa-spin fa-fw"></i> {{ __("Save") }}</button>
					@if ($update)
						<a id="print-invoice" href="{{ route('invoice.print', ['invoice' => $invoice->id]) }}" target="_blank" class="btn btn-outline-secondary{{ $update ? '' : ' disabled' }}"><i class="fas fa-print fa-fw"></i> {{ __("Print") }}</a>
					@endif
				</div>
				<div id="invoice-not-saved-message" class="col-12 pt-1 {{ $update ? 'invisible' : '' }}">
					<small class="text-danger">{{ __("Data is not saved.") }}</small>
				</div>
			</div>
		</form>
	</div>
@endsection

@section('modal')
	@include('shared.patient-notes-modal')
@endsection

@push('assets')
	@vite($entries)
@endpush

{{-- <div id="invoice-search-offcanvas" class="offcanvas offcanvas-end" tabindex="-1" aria-labelledby="invoice-search-offcanvas-label">
	<div class="offcanvas-header py-2">
		<h5 id="invoice-search-offcanvas-label" class="offcanvas-title">{{ __("My invoices") }}</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body d-flex flex-column pe-0">
		<div class="search-list d-flex align-items-center">
			<div class="dropdown xdropdown-sm">
				<button class="btn xbtn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
					{{ __("Les 3 derniers mois") }}
				</button>
				<ul class="dropdown-menu shadow">
					<li><button type="button" class="dropdown-item" data-limit="3">{{ __("Les 3 derniers mois") }} </button></li>
					<li><button type="button" class="dropdown-item" data-limit="6">{{ __("Les 6 derniers mois") }} </button></li>
					@foreach ($years as $year)
						<li><button type="button" class="dropdown-item" data-limit="{{ $year->year }}">{{ $year->year }} </button></li>
					@endforeach
				</ul>
			</div>
			<div class="opacity-50 fw-bold ms-3"><span class="invoice-search-total"></span> / {{ $count }}</div>
		</div>
		<div class="invoice-search-result flex-grow-1 overflow-auto mt-3 pe-3 user-select-none"></div>
	</div>
</div> --}}
