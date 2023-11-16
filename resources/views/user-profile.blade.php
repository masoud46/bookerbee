@extends('layouts.app', ['page_title' => '<i class="fas fa-user fa-fw me-2"></i>' . __('Profile')])

@php
	$default_country_code = config('project.default_country_code');
	$default_timezone = config('project.default_timezone');
	$user = Auth::user();
	$phone = $countries->keyBy('id')->get($user->phone_country_id)->prefix . ' ' . $user->phone_number;
@endphp

@section('content')
	<div class="container">
		<form id="profile-form" method="post" action="{{ route('account.profile.update') }}" class="form" autocomplete="off" autofill="off">
			<input type="hidden" name="verify_password">
			<input type="hidden" id="edit-email-url" value="{{ route('account.email') }}">
			<input type="hidden" id="edit-phone-url" value="{{ route('account.phone') }}">
			<input type="hidden" id="update-phone-url" value="{{ route('account.phone.update') }}">
			<input type="hidden" id="profile-saved">
			@method('put')
			@csrf
			<div class="form-group row">
				<div class="col-md-6 mt-4">
					<div class="mb-3 row">
						<label for="user-lastname" class="col-12 col-form-label"><span class="required-field">{{ __('Last name') }}</span></label>
						<div class="col-12">
							<input id="user-lastname" name="user-lastname" class="form-control  @error('user-lastname') is-invalid @enderror" value="{{ old('user-lastname', $user->lastname) }}">
							@error('user-lastname')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="user-firstname" class="col-12 col-form-label"><span class="required-field">{{ __('First name') }}</span></label>
						<div class="col-12">
							<input id="user-firstname" name="user-firstname" class="form-control  @error('user-firstname') is-invalid @enderror" value="{{ old('user-firstname', $user->firstname) }}">
							@error('user-firstname')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="user-code" class="col-12 col-form-label"><span class="required-field">{{ __('Code') }}</span></label>
						<div class="col-12">
							<input id="user-code" name="user-code" class="form-control  @error('user-code') is-invalid @enderror" value="{{ old('user-code', $user->code) }}">
							@error('user-code')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="user-titles" class="col-12 col-form-label"><span class="required-field">{{ __('Titles') }} <small class="text-muted">({{ __('one title per line') }})</small></span></label>
						<div class="col-12">
							@php($titles = implode("\n", json_decode($user->titles)))
							<textarea id="user-titles" name="user-titles" rows="3" class="form-control  @error('user-titles') is-invalid @enderror">{!! old('user-titles', $titles) !!}</textarea>
							@error('user-titles')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
				</div>
				<div class="col-md-6 mt-4">
					<div class="mb-3 d-flex align-items-center">
						<span class="me-2">{{ __('Email:') }}</span>
						<span class="fw-bold me-2">{{ $user->email }}</span>
						<div data-bs-toggle="modal" data-bs-target="#edit-email-modal">
							<div class="btn-edit btn-edit-email" data-bs-toggle="tooltip" data-bs-title="{{ __('Edit') }}">
								<i class="fas fa-edit"></i>
							</div>
						</div>
					</div>
					<div class="mb-3 d-flex align-items-center">
						<span class="me-2">{{ __('Phone:') }}</span>
						<span id="user-phone" class="fw-bold me-2">{{ $phone }}</span>
						<div data-bs-toggle="modal" data-bs-target="#edit-phone-modal">
							<div class="btn-edit btn-edit-phone" data-bs-toggle="tooltip" data-bs-title="{{ __('Edit') }}">
								<i class="fas fa-edit fa-fw"></i>
							</div>
						</div>
					</div>
					<div class="mb-3 row">
						<label for="user-fax_number" class="col-12 col-form-label"><span>{{ __('Fax') }}</span></label>
						<div class="col-12">
							@error('user-fax_number')
								@php($fax_error_class = 'is-invalid')
							@enderror
							<x-phone-number
								id="fax-number-component"
								class=" {{ $fax_error_class ?? '' }}"
								:countries="$countries"
								default-country-code="{{ $default_country_code }}"
								country-field="user-fax_country_id"
								number-field="user-fax_number"
								country="{{ old('user-fax_country_id', $user->fax_country_id) }}"
								number="{{ old('user-fax_number', $user->fax_number) }}" />
							@error('user-fax_number')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="user-timezone" class="col-12 col-form-label"><span class="required-field">{{ __('Time zone') }}</span></label>
						<div class="col-12">
							<select id="user-timezone" name="user-timezone" class="form-select  @error('user-timezone') is-invalid @enderror">
								<option value="" selected hidden>{{ __('Time zone') }}</option>
								@foreach ($timezones as $timezone)
									@php($selected = $user->timezone ? $timezone->name === $user->timezone : $timezone->name === $default_timezone)
									@if (old('user-timezone'))
										{{ $selected = old('user-timezone') === $timezone->name }}
									@endif
									<option value="{{ $timezone->name }}" {{ $selected ? 'selected' : '' }}>(UTC{{ $timezone->offset_str }}) {{ $timezone->name }}</option>
								@endforeach
							</select>
							@error('user-timezone')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
					</div>
					<div class="mb-3 row">
						<label for="user-bank_account" class="col-12 col-form-label"><span class="required-field">{{ __('Bank account') }}</span></label>
						<div class="mb-1">
							<input id="user-bank_account" name="user-bank_account" class="form-control  @error('user-bank_account') is-invalid @enderror" placeholder="{{ __('IBAN') }}" value="{{ old('user-bank_account', $user->bank_account) }}">
							@error('user-bank_account')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
						<div class="mb-1">
							<input id="user-bank_swift" name="user-bank_swift" class="form-control " placeholder="{{ __('SWIFT / BIC') }} ({{ __('optional') }})" value="{{ old('user-bank_swift', $user->bank_swift) }}">
							@error('user-bank_swift')
								<div class="invalid-feedback">{{ $message }}</div>
							@enderror
						</div>
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
					<button type="button" data-bs-toggle="modal" data-bs-target="#confirm-password-modal" class="btn btn-primary btn-spinner me-3 mb-1" data-saved="0">
						<i class="icon-visible fas fa-file-arrow-down fa-fw"></i>
						<div class="icon-hidden spinner">
							<div class="spinner-border"></div>
						</div>
						{{ __('Save') }}
					</button>
					<a role="button" href="{{ route('password.request') }}" class="btn btn-outline-secondary me-3 mb-1">
						<i class="fas fa-shield-halved fa-fw"></i>
						{{ __('Reset Password') }}
					</a>
				</div>
				<div id="profile-not-saved-message" class="col-12 {{ session()->has('error') ? '' : 'invisible' }}">
					<small class="text-danger">{{ __('Your profile is not saved.') }}</small>
				</div>
			</div>
		</form>
	</div>
@endsection


@section('modals')
	<div id="edit-email-modal" class="modal fade" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content shadow">
				<div class="modal-header shadow-sm">
					<h6 class="modal-title" id="edit-email-modal-title">{{ __('Change') }}</h6>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<div class="mb-4">
						<label for="new_email" class="form-label">{{ __('New email address') }}</label>
						<input id="new_email" class="form-control">
						<div class="invalid-feedback"></div>
					</div>
					<div class="text-muted fst-italic">{{ __('An email will be sent to the provided address in order to confirm the modification.') }}</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times fa-fw"> </i>{{ __('Cancel') }}</button>
					<button type="button" class="btn btn-primary btn-update-email">
						<i class="icon-visible far fa-paper-plane fa-fw"></i>
						{{ __('Send') }}
					</button>
				</div>
			</div>
		</div>
	</div>

	<div id="edit-phone-modal" class="modal fade" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content shadow">
				<div class="modal-header shadow-sm">
					<h6 class="modal-title" id="edit-phone-modal-title">{{ __('Change') }}</h6>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<div class="mb-4">
						<div class="edit-phone-request mb-4">
							<label for="phone_number" class="form-label">{{ __('New phone number') }}</label>
							<x-phone-number
								id="phone-number-component"
								:countries="$countries"
								default-country-code="{{ $default_country_code }}"
								country-field="phone_country_id"
								number-field="phone_number"
								country="{{ $user->phone_country_id }}"
								number="" />
							<div class="invalid-feedback d-block"></div>
						</div>
					</div>
					<div class="text-muted fst-italic">{{ __('A SMS will be sent to the provided phone number in order to confirm the modification.') }}</div>
					<div class="edit-phone-confirm">
						<div class="border-top mt-3 mb-1 pt-3">
							<div class="mb-3">{!! __('Please confirm the verification code sent to your new phone number.', [
							    'number' => '<span class="confirm-phone-number"></span>',
							]) !!}
							</div>
							<div class="d-flex align-items-center">
								<label for="verify-phone-code" class="form-label mb-0 me-2">{{ __('Code:') }}</label>
								<div class="w-50 me-2">
									<input id="verify-phone-code" name="verify-phone-code" class="form-control">
									<div class="invalid-feedback"></div>
								</div>
								<button type="button" class="btn btn-success btn-verify-code">
									<i class="icon-visible fas fa-check fa-fw"></i>
									{{ __('Confirm') }}
								</button>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times fa-fw"> </i>{{ __('Cancel') }}</button>
					<button type="button" class="btn btn-primary btn-update-phone">
						<i class="icon-visible fas fa-mobile-screen fa-fw"></i>
						{{ __('Send') }}
					</button>
				</div>
			</div>
		</div>
	</div>

	@include('shared.confirm-password-modal')
@endsection


@push('assets')
	@vite($entries)

	<script>
		window.laravel.messages.errorMissingToken = `{{ __('No token is provided.') }}`
	</script>
@endpush
