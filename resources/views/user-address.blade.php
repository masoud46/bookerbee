@extends('layouts.app', ['page_title' => '<i class="fas fa-location-dot fa-fw me-2"></i>' . __('Address')])

@php($default_country_code = config('project.default_country_code'))
@php($user = Auth::user())

@section('content')
	<div class="container">
		<form id="address-form" method="post" action="{{ route('account.address.update') }}" class="form" autocomplete="off" autofill="off">
			<input type="hidden" name="verify_password">
			<input type="hidden" id="address-saved">
			@method('put')
			@csrf
			<div class="form-group row">
				<div class="col-md-6 mt-4">
					<div class="row">
						<label for="user-address_line1" class="col-12 col-form-label"><span class="required-field">{{ __('Address') }} <small class="text-muted">({{ $locations[0]['code'] }})</small></span></label>
						<div class="col-12">
							<div class="mb-1">
								<input id="user-address_line1" name="user-address_line1" class="form-control  @error('user-address_line1') is-invalid @enderror" placeholder="{{ __('Line :line', ['line' => 1]) }}" value="{{ old('user-address_line1', $user->address_line1) }}">
								@error('user-address_line1')
									<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
							<div class="mb-1">
								<input id="user-address_line2" name="user-address_line2" class="form-control " placeholder="{{ __('Line :line', ['line' => 2]) }}" value="{{ old('user-address_line2', $user->address_line2) }}">
							</div>
							<div class="mb-3">
								<input id="user-address_line3" name="user-address_line3" class="form-control " placeholder="{{ __('Line :line', ['line' => 3]) }}" value="{{ old('user-address_line3', $user->address_line3) }}">
							</div>
							<div class="row">
								<div class="mb-1 col-4 col-xl-3">
									<input id="user-address_code" name="user-address_code" class="form-control  @error('user-address_code') is-invalid @enderror" placeholder="{{ __('Postal code') }}" value="{{ old('user-address_code', $user->address_code) }}">
									@error('user-address_code')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
								<div class="mb-1 col-8 col-xl-5">
									<input id="user-address_city" name="user-address_city" class="form-control  @error('user-address_city') is-invalid @enderror" placeholder="{{ __('City') }}" value="{{ old('user-address_city', $user->address_city) }}">
									@error('user-address_city')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
								<div class="mb-3 col-12 col-xl-4">
									<select id="user-address_country_id" name="user-address_country_id" class="form-select  @error('user-address_country_id') is-invalid @enderror">
										<option value="" selected hidden>{{ __('Country') }}</option>
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
					</div>
				</div>
				<div class="col-md-6 mt-4">
					<div class="row">
						<label for="user-address2_line1" class="col-12 col-form-label">
							<span>{{ __('Secondary address') }}</span>
							<small class="text-muted">({{ $locations[1]['code'] }})</small><br>
						</label>
						<div class="col-12">
							<div class="mb-1">
								<input id="user-address2_line1" name="user-address2_line1" class="form-control  @error('user-address2_line1') is-invalid @enderror" placeholder="{{ __('Line :line', ['line' => 1]) }}" value="{{ old('user-address2_line1', $user->address2_line1) }}">
								@error('user-address2_line1')
									<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
							<div class="mb-1">
								<input id="user-address2_line2" name="user-address2_line2" class="form-control " placeholder="{{ __('Line :line', ['line' => 2]) }}" value="{{ old('user-address2_line2', $user->address2_line2) }}">
							</div>
							<div class="mb-3">
								<input id="user-address2_line3" name="user-address2_line3" class="form-control " placeholder="{{ __('Line :line', ['line' => 3]) }}" value="{{ old('user-address2_line3', $user->address2_line3) }}">
							</div>
							<div class="row">
								<div class="mb-1 mb-xl-1 col-4 col-xl-3">
									<input id="user-address2_code" name="user-address2_code" class="form-control  @error('user-address2_code') is-invalid @enderror" placeholder="{{ __('Postal code') }}" value="{{ old('user-address2_code', $user->address2_code) }}">
									@error('user-address2_code')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
								<div class="mb-1 mb-xl-1 col-8 col-xl-5">
									<input id="user-address2_city" name="user-address2_city" class="form-control  @error('user-address2_city') is-invalid @enderror" placeholder="{{ __('City') }}" value="{{ old('user-address2_city', $user->address2_city) }}">
									@error('user-address2_city')
										<div class="invalid-feedback">{{ $message }}</div>
									@enderror
								</div>
								<div class="mb-2 col-12 col-xl-4">
									<select id="user-address2_country_id" name="user-address2_country_id" class="form-select  @error('user-address2_country_id') is-invalid @enderror">
										<option value="" selected hidden>{{ __('Country') }}</option>
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
					<div class="row mb-3">
						<small class="text-success fst-italic">{{ __('Without the secondary address, location :location will be disabled.', ['location' => '009b']) }}</small>
					</div>
				</div>
			</div>
			<div class="row border-top pt-1 mt-1 mb-4">
				<div class="col-12">
					<div class="pt-1"><small class="text-muted fst-italic">{!! __('Fields indicated by :star are required.', ['star' => '<span class="required-field me-2"></span>&nbsp;']) !!}</small></div>
				</div>
			</div>
			<div class="row my-4">
				<div class="col-12">
					<button type="button" data-bs-toggle="modal" data-bs-target="#confirm-password-modal" class="btn btn-primary btn-spinner me-3" data-saved="0">
						<i class="icon-visible fas fa-file-arrow-down fa-fw"></i>
						<div class="icon-hidden spinner">
							<div class="spinner-border"></div>
						</div>
						{{ __('Save') }}
					</button>
				</div>
				<div id="address-not-saved-message" class="col-12 pt-1 {{ session()->has('error') ? '' : 'invisible' }}">
					<small class="text-danger">{{ __('Your address is not saved.') }}</small>
				</div>
			</div>
		</form>
	</div>
@endsection


@section('modals')
	@include('shared.confirm-password-modal')
@endsection


@push('assets')
	@vite($entries)
@endpush
