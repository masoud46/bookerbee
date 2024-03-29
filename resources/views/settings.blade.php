@extends('layouts.app', ['page_title' => '<i class="fas fa-sliders fa-fw me-2"></i>' . __('Settings')])

@php
	$locales = LaravelLocalization::getSupportedLocales();
	$default_country_code = config('project.default_country_code');
	$cal_times = [];
	$time = Carbon\Carbon::parse('00:00:00');
	
	for ($i = 0; $i < 24; $i++) {
	    $cal_times[] = $time->format('H:i');
	    $time = $time->addHours(1);
	}
	$cal_times[] = '24:00';
@endphp

@section('content')
	<div class="container">
		<form id="settings-form" method="post" action="{{ route('settings.update') }}" class="form" autocomplete="off" autofill="off">
			@method('put')
			@csrf
			<input type="hidden" id="settings-saved">
			<div class="form-group row">
				<div class="col-12 mt-4">
					<h4 class="border-bottom pb-2">{{ __('Session') }}</h4>
				</div>
				<div class="col-sm-4 col-md-3 col-lg-2 mb-3">
					<label for="settings-duration" class="col-12 col-form-label">{{ __('Duration') }} <small class="text-muted">({{ __('minutes') }})</small></label>
					<input id="settings-duration" name="settings-duration" type="number" min="10" class="form-control @error('settings-duration') is-invalid @enderror" value="{{ old('settings-duration', $settings->duration ?? 10) }}" onkeydown="if([13,38,40].indexOf(event.which)===-1)event.preventDefault()">
					{{-- <select id="settings-duration" name="settings-duration" class="form-select">
						<option value="" selected hidden>{{ __('Duration') }}</option>
						@foreach ($durations as $duration)
							<option value="{{ $duration }}" {{ intval(old('settings-duration')) === $duration || $settings->duration === $duration ? 'selected' : '' }}>{{ $duration }}</option>
						@endforeach
					</select> --}}
					@error('settings-duration')
						<div class="invalid-feedback">{{ $message }}</div>
					@enderror
				</div>
				<div class="col-sm-8 col-md-7 col-lg-5 col-xl-4 mb-3">
					<label for="settings-location" class="col-12 col-form-label">{{ __('Default location') }}</label>
					<select id="settings-location" name="settings-location" class="form-select @error('settings-location') is-invalid @enderror">
						<option value="" selected hidden>{{ __('Location') }}</option>
						@foreach ($locations as $location)
							<option value="{{ $location->id }}" {{ intval(old('settings-location')) === $location->id || $settings->location === $location->id ? 'selected' : '' }}>{{ $location->code }} - {{ $location->description }}</option>
						@endforeach
					</select>
					@error('settings-location')
						<div class="invalid-feedback">{{ $message }}</div>
					@enderror
				</div>
				<div class="col-sm-4 col-md-3 col-lg-2 mb-3">
					<label for="settings-amount" class="col-12 col-form-label">{{ __('Default amount') }}</label>
					<div class="input-group @error('settings-amount') has-validation @enderror">
						<input id="settings-amount" name="settings-amount" class="form-control form-control @error('settings-amount') is-invalid @enderror" aria-describedby="input-group-sizing" placeholder="{{ __('Amount') }}" value="{{ old('settings-amount', $settings->amount) }}">
						<span class="input-group-text" id="input-group-sizing">€</span>
						@error('settings-amount')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
			</div>
			<div class="form-group row">
				<div class="col-12 mb-3">
					<div class="form-check form-switch">
						<input type="checkbox" value="1" id="settings-type_change_alert" name="settings-type_change_alert" role="switch" class="form-check-input" @checked(old('settings-type_change_alert', $settings->type_change_alert))>
						<label for="settings-type_change_alert" class="form-check-label">{{ __('Show alert when session type changes') }}</label>
					</div>
				</div>
			</div>
			<div class="form-group row">
				<div class="col-12 mt-4">
					<h4 class="border-bottom pb-2">{{ __('Calendar') }}</h4>
				</div>
				<div class="col-sm-6 col-md-3 col-lg-2 mb-3">
					<label for="settings-cal_min_time" class="col-12 col-form-label">{{ __('Start') }}</label>
					<select id="settings-cal_min_time" name="settings-cal_min_time" class="form-select @error('settings-cal_min_time') is-invalid @enderror">
						@foreach ($cal_times as $cal_time)
							@php($cal_min_time = "{$cal_time}:00")
							@if (!$loop->last)
								<option value="{{ $cal_min_time }}" {{ intval(old('settings-cal_min_time')) === $cal_min_time || $settings->cal_min_time === $cal_min_time ? 'selected' : '' }}>{{ $cal_time }}</option>
							@endif
						@endforeach
					</select>
					@error('settings-cal_min_time')
						<div class="invalid-feedback">{{ $message }}</div>
					@enderror
				</div>
				<div class="col-sm-6 col-md-3 col-lg-2 mb-3">
					<label for="settings-cal_max_time" class="col-12 col-form-label">{{ __('End') }}</label>
					<select id="settings-cal_max_time" name="settings-cal_max_time" class="form-select @error('settings-cal_max_time') is-invalid @enderror">
						@foreach ($cal_times as $cal_time)
							@php($cal_max_time = "{$cal_time}:00")
							@if (!$loop->first)
								<option value="{{ $cal_max_time }}" {{ intval(old('settings-cal_max_time')) === $cal_max_time || $settings->cal_max_time === $cal_max_time ? 'selected' : '' }}>{{ $cal_time }}</option>
							@endif
						@endforeach
					</select>
					@error('settings-cal_max_time')
						<div class="invalid-feedback">{{ $message }}</div>
					@enderror
				</div>
				<div class="col-sm-6 col-md-3 col-lg-2 mb-3">
					<label for="settings-cal_slot" class="col-12 col-form-label">{{ __('Time slots') }} <small class="text-muted">({{ __('minutes') }})</small></label>
					<select id="settings-cal_slot" name="settings-cal_slot" class="form-select @error('settings-cal_slot') is-invalid @enderror">
						<option value="" selected hidden>{{ __('Location') }}</option>
						@foreach ($cal_slots as $cal_slot)
							<option value="{{ $cal_slot }}" {{ intval(old('settings-cal_slot')) === $cal_slot || $settings->cal_slot === $cal_slot ? 'selected' : '' }}>{{ $cal_slot }}</option>
						@endforeach
					</select>
					@error('settings-cal_slot')
						<div class="invalid-feedback">{{ $message }}</div>
					@enderror
				</div>
			</div>
			<div class="form-group row mb-3">
				<div class="col-12 mt-4">
					<h4 class="border-bottom pb-2">{{ __('Email & SMS') }} <small class="text-muted">({{ __('except for cancellation') }})</small></h4>
				</div>
				<div class="col-md-6 mt-2 mb-3">
					@php($msg_email_checked = old('settings-msg_email_checked', $settings->msg_email_checked))
					<div class="col-12 mb-2">
						<div class="form-check form-switch">
							<input id="settings-msg_email_checked" name="settings-msg_email_checked" type="checkbox" role="switch" class="form-check-input" @checked($msg_email_checked)>
							<label class="form-check-label" for="settings-msg_email_checked">{{ __('Personal message by e-mail') }}</label>
						</div>
					</div>
					<div id="settings-msg_email" class="setting-messages {{ $msg_email_checked ? 'messages-visible' : '' }}">
						<div class="px-3 py-2 border rounded @error('settings-msg_email') is-invalid border-danger @enderror">
							@foreach ($locales as $key => $value)
								<small>{{ $locales[$key]['native'] }}</small>
								<textarea id="settings-msg_email-{{ $key }}" name="settings-msg_email-{{ $key }}" class="form-control form-control-sm mb-2 @error('settings-msg_email-' . $key) is-invalid @enderror" {{ $msg_email_checked ? '' : 'xxxdisabled' }}>{{ old('settings-msg_email-' . $key, $settings->msg_email[$key]) }}</textarea>
							@endforeach
						</div>
						@error('settings-msg_email')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-md-6 mt-2 mb-3">
					@php($msg_sms_checked = old('settings-msg_sms_checked', $settings->msg_sms_checked))
					<div class="col-12 mb-2">
						<div class="form-check form-switch">
							<input id="settings-msg_sms_checked" name="settings-msg_sms_checked" type="checkbox" role="switch" class="form-check-input" @checked($msg_sms_checked)>
							<label class="form-check-label" for="settings-msg_sms_checked">{{ __('Personal message by SMS') }}</label>
						</div>
					</div>
					<div id="settings-msg_sms" class="setting-messages {{ $msg_sms_checked ? 'messages-visible' : '' }}">
						<div class="px-3 py-2 border rounded @error('settings-msg_sms') is-invalid border-danger @enderror">
							@foreach ($locales as $key => $value)
								<small>{{ $locales[$key]['native'] }}</small>
								<input id="settings-msg_sms-{{ $key }}" name="settings-msg_sms-{{ $key }}" class="form-control form-control-sm mb-2 @error('settings-msg_sms-' . $key) is-invalid @enderror" {{ $msg_sms_checked ? '' : 'xxxdisabled' }} value="{{ old('settings-msg_sms-' . $key, $settings->msg_sms[$key]) }}">
							@endforeach
							<p class="mt-4 mb-2 py-1 border-bottom border-secondary opacity-50">{{ __('Standard characters') }}</p>
							<div>@ £ $ ¥ è é ù ì ò Ç Ø ø Å å Δ _ Φ Γ Λ Ω Π Ψ Σ Θ Ξ Æ æ ß É ! " # ¤ % & ' ( ) * + , - . / 0 1 2 3 4 5 6 7 8 9 : ; <=> ? ¡ A B C D E F G H I J K L M N O P Q R S T U V W X Y Z Ä Ö Ñ Ü § ¿ a b c d e f g h i j k l m n o p q r s t u v w x y z ä ö ñ ü à</div>
						</div>
						@error('settings-msg_sms')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
			</div>
			<div class="row mb-4">
				<div class="col-12">
					<div class="border-top pt-1"><small class="text-muted fst-italic">{{ __('All fields are mandatory.') }}</small></div>
				</div>
			</div>
			<div class="row my-4">
				<div class="col-12">
					<button type="submit" class="btn btn-primary btn-spinner me-3" data-saved="0">
						<i class="icon-visible fas fa-file-arrow-down fa-fw"></i>
						<div class="icon-hidden spinner">
							<div class="spinner-border"></div>
						</div>
						{{ __('Save') }}
					</button>
				</div>
				<div id="settings-not-saved-message" class="col-12 pt-1 {{ session()->has('error') ? '' : 'invisible' }}">
					<small class="text-danger">{{ __('Settings are not saved.') }}</small>
				</div>
			</div>
		</form>
	</div>
@endsection


@push('assets')
	@vite($entries)

	<script>
		window.laravel.messages.errorDurationSlot = `{{ __('The "Time slots" value must be equal or greater than the "Duration" value.') }}`
	</script>
@endpush
