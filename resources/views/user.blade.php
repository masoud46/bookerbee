@extends('layouts.app', ['page_title' => '<i class="far fa-user me-2"></i>' . __("My profile")])

@php($default_country_code = config('project.default_country_code'))
@php($user = Auth::user())

@section('content')
	<div class="container">
		<form id="user-form" method="post" action="{{ route('profile.update') }}" class="form" autocomplete="off" autofill="off">
			@method('put')
			@csrf
			<input type="hidden" id="user-saved">
			<div class="form-group row mt-4">
				<div class="col-md-6">
					<div class="mb-3 row">
						<label for="user-lastname" class="col-12 col-form-label"><span class="required-field">{{ __("Last name") }}</span></label>
						<div class="col-12">
							<input id="user-lastname" name="user-lastname" class="form-control @error('user-lastname') is-invalid @enderror" value="{{ old('user-lastname', $user->lastname) }}">
							@error('user-lastname')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="user-firstname" class="col-12 col-form-label"><span class="required-field">{{ __("First name") }}</span></label>
						<div class="col-12">
							<input id="user-firstname" name="user-firstname" class="form-control @error('user-firstname') is-invalid @enderror" value="{{ old('user-firstname', $user->firstname) }}">
							@error('user-firstname')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="user-code" class="col-12 col-form-label"><span class="required-field">{{ __("Code") }}</span></label>
						<div class="col-12">
							<input id="user-code" name="user-code" class="form-control @error('user-code') is-invalid @enderror" value="{{ old('user-code', $user->code) }}">
							@error('user-code')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="user-titles" class="col-12 col-form-label"><span class="required-field">{{ __("Titles") }} <small class="text-muted">({{ __("one title per line") }})</small></span></label>
						<div class="col-12">
							@php($titles = implode("\n", json_decode($user->titles)))
							<textarea id="user-titles" name="user-titles" rows="4" class="form-control @error('user-titles') is-invalid @enderror">{!! old('user-titles', $titles) !!}</textarea>
							@error('user-titles')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="user-email" class="col-12 col-form-label"><span class="required-field">{{ __("Email") }}</span></label>
						<div class="col-12">
							<input id="user-email" name="user-email" class="form-control @error('user-email') is-invalid @enderror" value="{{ old('user-email', $user->email) }}">
							@error('user-email')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="user-phone_number" class="col-12 col-form-label"><span class="required-field">{{ __("Phone") }}</span></label>
						<div class="col-12">
							@error('user-phone_number')
								@php($phon_error_class = 'is-invalid')
							@enderror
							<x-phone-number id="phone-number-component" class="{{ $phon_error_class ?? '' }}" :countries="$countries" default-country-code="{{ $default_country_code }}" country-field="user-phone_country_id" number-field="user-phone_number" country="{{ old('user-phone_country_id', $user->phone_country_id) }}" number="{{ old('user-phone_number', $user->phone_number) }}" />
							@error('user-phone_number')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="user-fax_number" class="col-12 col-form-label"><span>{{ __("Fax") }}</span></label>
						<div class="col-12">
							@error('user-fax_number')
								@php($fax_error_class = 'is-invalid')
							@enderror
							<x-phone-number id="fax-number-component" class="{{ $fax_error_class ?? '' }}" :countries="$countries" default-country-code="{{ $default_country_code }}" country-field="user-fax_country_id" number-field="user-fax_number" country="{{ old('user-fax_country_id', $user->fax_country_id) }}" number="{{ old('user-fax_number', $user->fax_number) }}" />
							@error('user-fax_number')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
				</div>
				<div class="col-md-6">
					<div class="row">
						<label for="user-address_line1" class="col-12 col-form-label"><span class="required-field">{{ __("Address") }} <small class="text-muted">(009)</small></span></label>
						<div class="col-12">
							<div class="mb-1">
								<input id="user-address_line1" name="user-address_line1" class="form-control @error('user-address_line1') is-invalid @enderror" placeholder="{{ __("Line :line", ['line' => 1]) }}" value="{{ old('user-address_line1', $user->address_line1) }}">
								@error('user-address_line1')
									<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
							<div class="mb-1">
								<input id="user-address_line2" name="user-address_line2" class="form-control" placeholder="{{ __("Line :line", ['line' => 2]) }}" value="{{ old('user-address_line2', $user->address_line2) }}">
							</div>
							<div class="mb-3">
								<input id="user-address_line3" name="user-address_line3" class="form-control" placeholder="{{ __("Line :line", ['line' => 3]) }}" value="{{ old('user-address_line3', $user->address_line3) }}">
							</div>
							<div class="row">
								<div class="mb-3 col-5">
									<input id="user-address_code" name="user-address_code" class="form-control @error('user-address_code') is-invalid @enderror" placeholder="{{ __("Postal code") }}" value="{{ old('user-address_code', $user->address_code) }}">
									@error('user-address_code')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
								<div class="mb-3 col-7">
									<input id="user-address_city" name="user-address_city" class="form-control @error('user-address_city') is-invalid @enderror" placeholder="{{ __("City") }}" value="{{ old('user-address_city', $user->address_city) }}">
									@error('user-address_city')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
							</div>
							<div class="mb-3">
								<select id="user-address_country_id" name="user-address_country_id" class="form-select @error('user-address_country_id') is-invalid @enderror">
									<option value="" selected hidden>{{ __("Country") }}</option>
									@foreach ($countries as $country)
										@php($selected = $user->address_country_id ? $country->id === $user->address_country_id : $country->code === $default_country_code)
										@if (old('user-address_country_id'))
											{{ $selected = intval(old('user-address_country_id')) === $country->id }}
										@endif
										<option value="{{ $country->id }}" {{ $selected ? 'selected' : '' }}>{{ $country->name }}</option>
									@endforeach
								</select>
								@error('user-address_country_id')
									<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
						</div>
					</div>
					<div class="row">
						<label for="user-address2_line1" class="col-12 col-form-label">
							<span>{{ __("Secondary address") }}</span>
							<small class="text-muted">(009b)</small><br>
							<small class="text-muted fst-italic">{{ __("Without the secondary address, location :location will be disabled.", ['location' => '009b']) }}</small>
						</label>
						<div class="col-12">
							<div class="mb-1">
								<input id="user-address2_line1" name="user-address2_line1" class="form-control @error('user-address2_line1') is-invalid @enderror" placeholder="{{ __("Line :line", ['line' => 1]) }}" value="{{ old('user-address2_line1', $user->address2_line1) }}">
								@error('user-address2_line1')
									<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
							<div class="mb-1">
								<input id="user-address2_line2" name="user-address2_line2" class="form-control" placeholder="{{ __("Line :line", ['line' => 2]) }}" value="{{ old('user-address2_line2', $user->address2_line2) }}">
							</div>
							<div class="mb-3">
								<input id="user-address2_line3" name="user-address2_line3" class="form-control" placeholder="{{ __("Line :line", ['line' => 3]) }}" value="{{ old('user-address2_line3', $user->address2_line3) }}">
							</div>
							<div class="row">
								<div class="mb-3 col-5">
									<input id="user-address2_code" name="user-address2_code" class="form-control @error('user-address2_code') is-invalid @enderror" placeholder="{{ __("Postal code") }}" value="{{ old('user-address2_code', $user->address2_code) }}">
									@error('user-address2_code')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
								<div class="mb-3 col-7">
									<input id="user-address2_city" name="user-address2_city" class="form-control @error('user-address2_city') is-invalid @enderror" placeholder="{{ __("City") }}" value="{{ old('user-address2_city', $user->address2_city) }}">
									@error('user-address2_city')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
							</div>
							<div class="mb-3">
								<select id="user-address2_country_id" name="user-address2_country_id" class="form-select @error('user-address2_country_id') is-invalid @enderror">
									<option value="" selected hidden>{{ __("Country") }}</option>
									@foreach ($countries as $country)
										@php($selected = $user->address2_country_id ? $country->id === $user->address2_country_id : $country->code === $default_country_code)
										@if (old('user-address2_country_id'))
											{{ $selected = intval(old('user-address2_country_id')) === $country->id }}
										@endif
										<option value="{{ $country->id }}" {{ $selected ? 'selected' : '' }}>{{ $country->name }}</option>
									@endforeach
								</select>
								@error('user-address2_country_id')
									<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row mb-4">
				<div class="col-12">
					<div class="border-top pt-1"><small class="text-muted fst-italic">{!! __("Fields indicated by :star are required.", ['star' => '<span class="required-field me-2"></span>&nbsp;']) !!}</small></div>
				</div>
			</div>
			<div class="row my-4">
				<div class="col-12">
					<button type="submit" class="btn btn-primary btn-fa-spinner me-3" data-saved="0"><i class="icon-visible fas fa-file-arrow-down fa-fw"></i><i class="icon-hidden fas fa-spinner fa-spin fa-fw"></i> {{ __("Save") }}</button>
				</div>
				<div id="user-not-saved-message" class="col-12 pt-1 {{ session()->has('error') ? '' : 'invisible' }}">
					<small class="text-danger">{{ __("Your profile is not saved.") }}</small>
				</div>
			</div>
		</form>
	</div>
@endsection


@push('assets')
	@vite($entries)
@endpush
