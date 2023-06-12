<x-mail::message>

<x-mail::hello :name="explode(', ', $event['extendedProps']['patient']['name'])[1]" />

{{ __('Your appointment has been rescheduled, please take note of the new schedule:') }}

<x-mail::appointment
	:firstname="ucfirst(Auth::user()->firstname)"
	:lastname="strtoupper(Auth::user()->lastname)"
	:date="Carbon\Carbon::parse($event['localStart'])->translatedFormat('l j F Y')"
	:start="Carbon\Carbon::parse($event['localStart'])->translatedFormat('H:i')"
	:end="Carbon\Carbon::parse($event['localEnd'])->translatedFormat('H:i')"
/>

<p style="font-size: 14px; font-style: italic;">
{{ __('The initial schedule was planned for :date, from :start to :end.', [
	'date' => Carbon\Carbon::parse($old_event['localStart'])->translatedFormat('l j F Y'),
	'start' => Carbon\Carbon::parse($old_event['localStart'])->translatedFormat('H:i'),
	'end' => Carbon\Carbon::parse($old_event['localEnd'])->translatedFormat('H:i'),
]) }}
</p>

<x-mail::import-appointment :url="route('event.export', ['id' => $event['hash_id']])" />

<x-mail::cancel-appointment :email="Auth::user()->email" :phone="$event['user_phone']" />

<x-mail::no-reply />

</x-mail::message>
