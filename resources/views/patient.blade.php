@extends('layouts.app', ['page_title' => '<i class="fas fa-user me-2"></i>' . __('Patient')])

@php
	$default_country_code = config('project.default_country_code');
	$update = isset($patient);
@endphp

@section('content')
	<div class="container">
		<form id="patient-form" method="post" action="{{ route('patient.store') }}" class="form" autocomplete="off" autofill="off">
			@csrf
			<input type="hidden" id="patient-phone_prefix" value="{{ $update ? $patient->phone_prefix : '' }}">
			<input type="hidden" id="patient-phone_number" value="{{ $update ? $patient->phone_number : '' }}">
			<input type="hidden" id="patient-notes-fetch-url" value="{{ route('patient.notes') }}">
			<input type="hidden" id="patient-notes-store-url" value="{{ route('patient.notes.store') }}">
			<input type="hidden" id="invoice-new-url" value="{{ route('invoice.new', ['patient' => '?id']) }}">
			<input type="hidden" id="form-key" name="form-key" value="{{ $update ? $key : '' }}">
			<input type="hidden" id="patient-saved">
			<div class="row mt-4">
				<div class="col-8 col-sm-5 offset-sm-4 col-lg-7 offset-lg-2 mb-3">
					<div class="form-check form-switch">
						<input id="patient-category" name="patient-category" type="checkbox" class="form-check-input" role="switch" @checked(old('patient-category', !$update || $patient->category === 1))>
						<label class="form-check-label" for="patient-category">{{ __('CNS patient') }}</label>
					</div>
				</div>
				<div class="col-4 col-sm-3">
					@if ($update)
						<button type="button" class="btn btn-sm btn-success float-end" data-bs-toggle="modal" data-bs-target="#patient-notes-modal"><i class="far fa-file-lines fa-fw"></i> {{ __('Notes') }}</button>
					@endif
				</div>
				<div class="col-lg-6">
					<div class="mb-3 row">
						<label for="patient-code" class="col-sm-4 col-form-label text-sm-end"><span class="required-field">{{ __('Registration number') }}</span></label>
						<div class="col-sm-8">
							<input id="patient-code" name="patient-code" class="form-control @error('patient-code') is-invalid @enderror" value="{{ old('patient-code', $update ? $patient->code : '') }}">
							@error('patient-code')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="patient-lastname" class="col-sm-4 col-form-label text-sm-end"><span class="required-field">{{ __('Last name') }}</span></label>
						<div class="col-sm-8">
							<input id="patient-lastname" name="patient-lastname" class="form-control @error('patient-lastname') is-invalid @enderror" value="{{ old('patient-lastname', $update ? $patient->lastname : '') }}">
							@error('patient-lastname')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="patient-firstname" class="col-sm-4 col-form-label text-sm-end"><span class="required-field">{{ __('First name') }}</span></label>
						<div class="col-sm-8">
							<input id="patient-firstname" name="patient-firstname" class="form-control @error('patient-firstname') is-invalid @enderror" value="{{ old('patient-firstname', $update ? $patient->firstname : '') }}">
							@error('patient-firstname')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="patient-email" class="col-sm-4 col-form-label text-sm-end">{{ __('Email') }}</label>
						<div class="col-sm-8">
							<input id="patient-email" name="patient-email" class="form-control @error('patient-email') is-invalid @enderror" value="{{ old('patient-email', $update ? $patient->email : '') }}">
							@error('patient-email')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="patient-phone_number" class="col-sm-4 col-form-label text-sm-end">{{ __('Phone') }}</label>
						<div class="col-sm-8">
							@error('patient-phone_number')
								@php($phone_error_class = 'is-invalid')
							@enderror
							<x-phone-number id="phone-number-component" class="{{ $phone_error_class ?? '' }}" :countries="$countries" default-country-code="{{ $default_country_code }}" country-field="patient-phone_country_id" number-field="patient-phone_number" country="{{ old('patient-phone_country_id', $update ? $patient->phone_country_id : '') }}" number="{{ old('patient-phone_number', $update ? $patient->phone_number : '') }}" />
							@error('patient-phone_number')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
				</div>
				<div class="col-lg-6">
					<div class="row">
						<label for="patient-address_line1" class="col-sm-4 col-form-label text-sm-end"><span class="required-field">{{ __('Address') }}</span></label>
						<div class="col-sm-8">
							<div class="mb-1">
								<input id="patient-address_line1" name="patient-address_line1" class="form-control @error('patient-address_line1') is-invalid @enderror" placeholder="{{ __('Line :line', ['line' => 1]) }}" value="{{ old('patient-address_line1', $update ? $patient->address_line1 : '') }}">
								@error('patient-address_line1')
									<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
							<div class="mb-1">
								<input id="patient-address_line2" name="patient-address_line2" class="form-control" placeholder="{{ __('Line :line', ['line' => 2]) }}" value="{{ old('patient-address_line2', $update ? $patient->address_line2 : '') }}">
							</div>
							<div class="mb-3">
								<input id="patient-address_line3" name="patient-address_line3" class="form-control" placeholder="{{ __('Line :line', ['line' => 3]) }}" value="{{ old('patient-address_line3', $update ? $patient->address_line3 : '') }}">
							</div>
							<div class="row">
								<div class="mb-3 col-5">
									<input id="patient-address_code" name="patient-address_code" class="form-control @error('patient-address_code') is-invalid @enderror" placeholder="{{ __('Postal code') }}" value="{{ old('patient-address_code', $update ? $patient->address_code : '') }}">
									@error('patient-address_code')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
								<div class="mb-3 col-7">
									<input id="patient-address_city" name="patient-address_city" class="form-control @error('patient-address_city') is-invalid @enderror" placeholder="{{ __('City') }}" value="{{ old('patient-address_city', $update ? $patient->address_city : '') }}">
									@error('patient-address_city')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
							</div>
							<div class="mb-3">
								<select style="appearance:none;" id="patient-address_country_id" name="patient-address_country_id" class="form-select @error('patient-address_country_id') is-invalid @enderror">
									<option value="" selected hidden>{{ __('Country') }}</option>
									@foreach ($countries as $country)
										@php($selected = $update ? $country->id === $patient->address_country_id : $country->code === $default_country_code)
										@if (old('patient-address_country_id'))
											{{ $selected = intval(old('patient-address_country_id')) === $country->id }}
										@endif
										<option value="{{ $country->id }}" {{ $selected ? 'selected' : '' }}>{{ $country->name }}</option>
									@endforeach
								</select>
								@error('patient-address_country_id')
									<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row mb-4">
				<div class="col-12">
					<div class="border-top pt-1"><small class="text-muted fst-italic">{!! __('Fields indicated by :star are required.', ['star' => '<span class="required-field me-2"></span>&nbsp;']) !!}</small></div>
				</div>
			</div>
			<div class="row my-4">
				<div class="col-12">
					<button type="submit" id="save-patient" class="btn btn-primary btn-fa-spinner me-3" data-saved="0"><i class="icon-visible fas fa-file-arrow-down fa-fw"></i><i class="icon-hidden fas fa-spinner fa-spin fa-fw"></i> {{ __('Save') }}</button>
					@if ($update)
						<a id="new-invoice" href="{{ route('invoice.new', ['patient' => $patient->id]) }}" class="btn btn-outline-secondary {{ session()->has('error') ? 'disabled' : '' }}"><i class="fas fa-receipt fa-fw"></i> {{ __('New invoice') }}</a>
					@endif
				</div>
				<div id="patient-not-saved-message" class="col-12 pt-1 {{ !$update || session()->has('error') ? '' : 'invisible' }}">
					<small class="text-danger">{{ __('Data is not saved.') }}</small>
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
