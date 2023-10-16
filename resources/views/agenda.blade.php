@extends('layouts.app', ['page_title' => '<i class="far fa-calendar-days me-2"></i>' . __('Agenda') . '<span class="ms-2 text-muted agenda-timezone">' . Auth::user()->timezone . '</span>'])

@php
	$default_country_code = config('project.default_country_code');
	
	$ACTIONS = [
	    'ADD' => 'add',
	    'CANCEL' => 'cancel',
	    'UPDATE' => 'update_event',
	    'LOCK' => 'lock',
	    'UNLOCK' => 'unlock',
	    'UPDATE_LOCK' => 'update_lock',
	];
	
	$FREQ = [
	    'NONE' => [
	        'value' => 0,
	        'title' => 'none',
	    ],
	    'DAILY' => [
	        'value' => 3,
	        'title' => 'daily',
	    ],
	    'WEEKLY' => [
	        'value' => 2,
	        'title' => 'weekly',
	    ],
	];
	
	$date = \Carbon\Carbon::now()->next('Monday');
	$week_days = [$date->isoFormat('ddd')];
	for ($x = 0; $x < 6; $x++) {
	    $date = $date->addDay();
	    $week_days[] = $date->isoFormat('ddd');
	}
@endphp


@section('content')
	<div class="container-fluid">
		<div class="form-group row mt-4">
			<div class="col-12 position-relative">
				<div id="app-calendar"></div>
				<div class="app-calendar-overlay position-absolute top-0 start-0 bottom-0 end-0 d-flex justify-content-center align-items-center">
					<div class="spinner-border text-primary opacity-75" role="status">
						<span class="visually-hidden">{{ __('Loading...') }}</span>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection


@section('modals')
	<div id="calendar-modal" class="modal fade" tabindex="-1" aria-labelledby="calendar-modal-title" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content shadow">
				<div class="modal-header">
					<h5 class="modal-title"></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body p-4" tabindex="-1">
					<div class="calendar-event-date-time container-fluide bg-secondary bg-opacity-10 rounded-1 border border-secondary border-opacity-50 py-2 mb-3">
						<div class="row font-monospace">
							<div class="col calendar-event-start text-end">
								<span class="calendar-event-start-date"></span>
								<span class="calendar-event-start-space mx-1"></span>
								<span class="text-end calendar-event-start-time"></span>
							</div>
							<div class="col-auto calendar-event-to text-info px-1"><i class="fas fa-angles-right"></i></div>
							<div class="col calendar-event-end">
								<span class="calendar-event-end-date"></span>
								<span class="calendar-event-end-space mx-1"></span>
								<span class="text-end calendar-event-end-time"></span>
							</div>
						</div>
						<div class="calendar-event calendar-event-all-day text-muted text-center">{{ __('All day') }}</div>
					</div>
					<div class="calendar-event calendar-event-rdv mb-3">
						<label>{{ __('Location') }}</label>
						<select id="calendar-event-location" class="form-select {{ $locations->count() === 1 ? 'pe-none' : '' }}" {{ $locations->count() === 1 ? 'disabled' : '' }}>
							@foreach ($locations as $location)
								<option value="{{ $location->id }}" {{ $location->disabled ? 'disabled' : '' }}>{{ $location->code }} - {{ $location->description }}</option>
							@endforeach
						</select>
				<div class="mb-3">
					<div id="event-location-address">
						<div class="p-1">
								<div class="row g-1">
									<div class="col-sm-8 mb-1">
										<input id="location-address_name" name="location-address_name" class="form-control form-control-sm" placeholder="{{ __('Location name') }} *">
									</div>
								</div>
								<div class="row g-1">
									<div class="col-12 mb-1">
										<input id="location-address_address" name="location-address_address" class="form-control form-control-sm" placeholder="{{ __('Address') }} *">
									</div>
								</div>
								<div class="row g-1">
									<div class="col-sm-3">
										<input id="location-address_code" name="location-address_code" class="form-control form-control-sm" placeholder="{{ __('Postal code') }} *">
									</div>
									<div class="col-sm-5">
										<input id="location-address_city" name="location-address_city" class="form-control form-control-sm" placeholder="{{ __('City') }} *">
									</div>
									<div class="col-sm-4">
										<select id="location-address_country_id" name="location-address_country_id" class="form-select form-select-sm">
											<option value="" selected hidden>{{ __('Country') }}</option>
											@foreach ($countries as $country)
													@php($selected = $country['code'] === $default_country_code ? 'selected' : '')
													<option value="{{ $country['id'] }}" {{ $selected }}>{{ $country['name'] }}</option>
											@endforeach
										</select>
									</div>
								</div>
							<div class="invalid-feedback">{{ __('All fields are mandatory.') }}</div>
						</div>
					</div>
				</div>
						<label>{{ __('Patient') }}</label>
						<x-patient-picker
							id="patient-picker-component"
							autocomplete-url="{{ route('patient.autocomplete') }}"
							placeholder="{{ __('Last name / First name / Reg. number') }}"
							helper-text="{{ __('Start by typing three characters.') }}" />
					</div>
					<div class="calendar-event calendar-event-slot mb-3">
						<label>{{ __('Title') }} <small class="text-muted">({{ __('optional') }})</small></label>
						<input type="text" class="calendar-event-title form-control form-control-sm">
					</div>
					<div class="calendar-event calendar-event-recurr">
						<div class="form-check form-switch ps-0">
							<input class="event-recurr-switch form-check-input ms-0" type="checkbox" role="switch" id="event-recurr-switch"><label class="form-check-label ps-2" for="event-recurr-switch">{{ __('Recurrent') }}</label>
						</div>
						<form class="event-recurr-form">
							<div class="event-recurr-container">
								<div class="row mb-1">
									<div class="col-6">
										<label><small>{{ __('Type') }}</small></label>
										<select class="form-select form-select-sm event-recurr-frequency">
											<option value="{{ $FREQ['NONE']['title'] }}" selected hidden></option>
											<option value="{{ $FREQ['DAILY']['title'] }}">{{ __('Daily') }}</option>
											<option value="{{ $FREQ['WEEKLY']['title'] }}">{{ __('Weekly') }}</option>
										</select>
									</div>
									<div class="col-6 event-recurr-limit-container">
										<label><small>{{ __('Limit') }}</small></label>
										<x-resetable-date
											class="resetable-date-container event-recurr-limit form-control-sm"
											inputId="event-recurr-limit"
											inputName="event-recurr-limit"
											inputValue="" />
									</div>
								</div>
								<div class="row event-recurr-days">
									<div class="col-12 mt-2">
										<div class="font-monospace mt-1 d-flex justify-content-between">
											<input class="event-recurr-day event-recurr-day-0 btn-check" type="checkbox" id="event-recurr-mo" autocomplete="off">
											<label class="btn btn-sm btn-outline-primary" for="event-recurr-mo">{{ $week_days[0] }}</label>
											<input class="event-recurr-day event-recurr-day-1 btn-check" type="checkbox" id="event-recurr-tu" autocomplete="off">
											<label class="btn btn-sm btn-outline-primary" for="event-recurr-tu">{{ $week_days[1] }}</label>
											<input class="event-recurr-day event-recurr-day-2 btn-check" type="checkbox" id="event-recurr-we" autocomplete="off">
											<label class="btn btn-sm btn-outline-primary" for="event-recurr-we">{{ $week_days[2] }}</label>
											<input class="event-recurr-day event-recurr-day-3 btn-check" type="checkbox" id="event-recurr-th" autocomplete="off">
											<label class="btn btn-sm btn-outline-primary" for="event-recurr-th">{{ $week_days[3] }}</label>
											<input class="event-recurr-day event-recurr-day-4 btn-check" type="checkbox" id="event-recurr-fr" autocomplete="off">
											<label class="btn btn-sm btn-outline-primary" for="event-recurr-fr">{{ $week_days[4] }}</label>
											<input class="event-recurr-day event-recurr-day-5 btn-check" type="checkbox" id="event-recurr-sa" autocomplete="off">
											<label class="btn btn-sm btn-outline-primary" for="event-recurr-sa">{{ $week_days[5] }}</label>
											<input class="event-recurr-day event-recurr-day-6 btn-check" type="checkbox" id="event-recurr-su" autocomplete="off">
											<label class="btn btn-sm btn-outline-primary" for="event-recurr-su">{{ $week_days[6] }}</label>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
					<div class="calendar-event calendar-event-rdv calendar-event-rdv-info">
						<div class="d-none"><i class='far fa-user fa-fw me-2'></i><span class="event-patient-name"></span></div>
						<div><i class='far fa-envelope fa-fw me-2'></i><span class="event-patient-email"></span></div>
						<div><i class='fas fa-mobile-screen-button fa-fw me-2'></i><span class="event-patient-phone"></span></div>
						<div class="calendar-event-has-email text-dark bg-warning bg-opacity-10 rounded-1 mt-3 px-4 py-3">
							<div class="calendar-event calendar-event-{{ $ACTIONS['ADD'] }}">{{ __('After saving the appointment, a detailed notification will be sent to the patient.') }}</div>
							<div class="calendar-event calendar-event-{{ $ACTIONS['CANCEL'] }}">{{ __('Following the cancelation, an informative notification will be sent to the patient.') }}</div>
							<div class="calendar-event calendar-event-{{ $ACTIONS['UPDATE'] }}">{{ __('Following the update, a notification about the applied modifications will be sent to the patient.') }}</div>
						</div>
						<div class="calendar-event-no-notification text-bg-warning rounded-1 mt-3 px-4 py-3">
							{{ __('This patient does not have an email address, nor a phone number. No notification will be sent.') }}
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-outline-dark me-2" data-bs-dismiss="modal">{{ __('Close') }}</button>
					<button type="button" class="calendar-event-action btn">action</button>
				</div>
			</div>
		</div>
	</div>
@endsection


@push('assets')
	<script>
		@foreach ($ACTIONS as $key => $value)
			const EVENT_ACTION_{{ $key }} = '{{ $value }}'
		@endforeach

		window.laravel.messages.createAppointment = "{{ __('Create un appointment') }}"
		window.laravel.messages.lockSlot = "{{ __('Lock this slot') }}"
		window.laravel.settings = {!! json_encode($settings) !!}
		window.laravel.agenda = {
			url: `{{ route('event.fetch') }}`,
			lock: {{ in_array('agenda_lock', Auth::user()->features) ? 'true' : 'false' }},
			timezone: '{{ Auth::user()->timezone }}',
			countries: {!! json_encode($countries) !!},
			freq: {!! json_encode($FREQ) !!},
			actions: {
				[EVENT_ACTION_ADD]: {
					header: `{{ __('Add') }}`,
					btn: `{{ __('Save the appointment') }}`,
					url: `{{ route('event.add') }}`,
					message: `{{ __('Appointment has been saved successfully.') }}`,
				},
				[EVENT_ACTION_CANCEL]: {
					header: `{{ __('Cancel') }}`,
					btn: `{{ __('Cancel the appointment') }}`,
					url: `{{ route('event.cancel', ['event' => '?id']) }}`,
					message: `{{ __('Appointment has been canceled.') }}`,
				},
				[EVENT_ACTION_UPDATE]: {
					header: `{{ __('Update') }}`,
					btn: `{{ __('Update the appointment') }}`,
					url: `{{ route('event.update', ['event' => '?id']) }}`,
					message: `{{ __('Appointment has been updated.') }}`,
				},
				[EVENT_ACTION_LOCK]: {
					header: `{{ __('Lock') }}`,
					btn: `{{ __('Lock the slot') }}`,
					url: `{{ route('event.add') }}`,
					message: `{{ __('Slot has been locked successfully.') }}`,
				},
				[EVENT_ACTION_UNLOCK]: {
					header: `{{ __('Unlock') }}`,
					btn: `{{ __('Unlock the slot') }}`,
					url: `{{ route('event.cancel', ['event' => '?id']) }}`,
					message: `{{ __('Slot has been unlocked.') }}`,
				},
				[EVENT_ACTION_UPDATE_LOCK]: {
					header: `{{ __('Update') }}`,
					btn: `{{ __('Update the slot') }}`,
					url: `{{ route('event.update', ['event' => '?id']) }}`,
					message: `{{ __('Slot has been updated.') }}`,
				},
			},
		}
	</script>
	@vite($entries)
@endpush
