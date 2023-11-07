<x-mail::message>

<x-mail::hello :name="explode(', ', $event['extendedProps']['patient']['name'])[1]" />

{{ $old_event ?
	__('Your original appointment of :date at :start has been rescheduled. The new appointment is:', [
		'date' => Carbon\Carbon::parse($old_event['localStart'])->translatedFormat('l j F Y'),
		'start' => Carbon\Carbon::parse($old_event['localStart'])->translatedFormat('H:i'),
	]) :
	__('The location for you appointment of :date at :start has been changed. The new appointment is:', [
		'date' => Carbon\Carbon::parse($event['localStart'])->translatedFormat('l j F Y'),
		'start' => Carbon\Carbon::parse($event['localStart'])->translatedFormat('H:i'),
	])
}}

<x-mail::appointment
	:firstname="ucfirst(Auth::user()->firstname)"
	:lastname="strtoupper(Auth::user()->lastname)"
	:date="Carbon\Carbon::parse($event['localStart'])->translatedFormat('l j F Y')"
	:time="Carbon\Carbon::parse($event['localStart'])->translatedFormat('H:i')"
	:duration="$event['duration'] . ' ' . __('minutes')"
	:address="$event['address']"
	:message="isset($event['msg_email']) ? $event['msg_email'] : null"
/>

<x-mail::import-appointment :url="LaravelLocalization::getLocalizedURL(app()->getLocale(), route('event.export', ['id' => $event['hash_id']]))" />

<x-mail::cancel-appointment :email="Auth::user()->email" :phone="$event['user_phone']" />

<x-mail::no-reply />

</x-mail::message>
