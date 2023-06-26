@extends('layouts.app', ['page_title' => '<i class="fas fa-sliders me-2"></i>' . __('Settings')])

@php
	$default_country_code = config('project.default_country_code');
	$cal_slots = [10, 15, 30, 45, 60];
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
				<div class="col-sm-8 col-md-7 col-lg-5 col-xl-4 mb-3">
					<label for="settings-location" class="col-12 col-form-label"><span>{{ __('Default location') }}</span></label>
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
					<label for="settings-amount" class="col-12 col-form-label"><span>{{ __('Default amount') }}</span></label>
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
					<div class="form-check">
						<input type="checkbox" value="1" id="settings-type_change_alert" name="settings-type_change_alert" class="form-check-input" {{ old('settings-type_change_alert', $settings->type_change_alert) ? 'checked' : '' }}>
						<label for="flexCheckChecked" class="form-check-label">
							{{ __('Show alert when session type changes') }}
						</label>
					</div>
				</div>
			</div>
			<div class="form-group row">
				<div class="col-12 mt-4">
					<h4 class="border-bottom pb-2">{{ __('Calendar') }}</h4>
				</div>
				<div class="col-sm-6 col-md-3 col-lg-2 mb-3">
					<label for="settings-cal_min_time" class="col-12 col-form-label"><span>{{ __('Start') }}</span></label>
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
					<label for="settings-cal_max_time" class="col-12 col-form-label"><span>{{ __('End') }}</span></label>
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
				<div class="col-sm-6 col-md-4 col-lg-3 col-xl-2 mb-3">
					<label for="settings-cal_slot" class="col-12 col-form-label"><span>{{ __('Time slots') }}</span></label>
					<select id="settings-cal_slot" name="settings-cal_slot" class="form-select @error('settings-cal_slot') is-invalid @enderror">
						<option value="" selected hidden>{{ __('Location') }}</option>
						@foreach ($cal_slots as $cal_slot)
							<option value="{{ $cal_slot }}" {{ intval(old('settings-cal_slot')) === $cal_slot || $settings->cal_slot === $cal_slot ? 'selected' : '' }}>{{ $cal_slot }} {{ __('minutes') }}</option>
						@endforeach
					</select>
					@error('settings-cal_slot')
						<div class="invalid-feedback">{{ $message }}</div>
					@enderror
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
						<div class="icon-hidden spinner"><div class="spinner-border"></div></div>
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
@endpush
