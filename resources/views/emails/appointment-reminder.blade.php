@php
    Carbon\Carbon::setlocale(config('app.locale'));
	$start = Carbon\Carbon::parse($event['start'])->setTimezone($event['timezone']);
	$end = Carbon\Carbon::parse($event['end'])->setTimezone($event['timezone']);
@endphp
<x-mail::message>

<x-mail::hello :name="$event['patient_firstname']" />

{{ __('Your next appointment with the following details, will start in about :time hours.', ['time' => config('project.reminder_email_time')]) }}

<x-mail::appointment
	:firstname="ucfirst($event['user_firstname'])"
	:lastname="strtoupper($event['user_lastname'])"
	:date="$start->translatedFormat('l j F Y')"
	:start="$start->translatedFormat('H:i')"
	:end="$end->translatedFormat('H:i')"
/>

<x-mail::cancel-appointment :email="$event['user_email']" :phone="$event['user_phone']" />

<x-mail::regards />

</x-mail::message>
