@php
$start = Carbon\Carbon::parse($event['start'])->setTimezone($event['timezone']);
$end = Carbon\Carbon::parse($event['end'])->setTimezone($event['timezone']);
@endphp
<x-mail::message>

<x-mail::hello :name="$event['patient_firstname']" />

{{ __('Your next appointment with the following details, will start in about :time hours.', ['time' => $event['remaining_time']]) }}

<x-mail::appointment
	:firstname="ucfirst($event['user_firstname'])"
	:lastname="strtoupper($event['user_lastname'])"
	:date="$start->translatedFormat('l j F Y')"
	:time="$start->translatedFormat('H:i')"
	:duration="$event['duration'] . ' ' . __('minutes')"
	:address="$event['address']"
	:message="isset($event['msg_email']) ? $event['msg_email'] : null"
/>

<x-mail::cancel-appointment :email="$event['user_email']" :phone="$event['user_phone']" />

<x-mail::no-reply />

</x-mail::message>
