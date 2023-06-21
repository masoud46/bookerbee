<x-mail::message>

<x-mail::hello :name="ucfirst(trim(explode(', ', $event['extendedProps']['patient']['name'])[1]))" />

{{ __("We confirm your appointment with the following details:") }}

<x-mail::appointment
	:firstname="ucfirst(Auth::user()->firstname)"
	:lastname="strtoupper(Auth::user()->lastname)"
	:date="Carbon\Carbon::parse($event['localStart'])->translatedFormat('l j F Y')"
	:start="Carbon\Carbon::parse($event['localStart'])->translatedFormat('H:i')"
	:end="Carbon\Carbon::parse($event['localEnd'])->translatedFormat('H:i')"
/>

<x-mail::import-appointment :url="route('event.export', ['id' => $event['hash_id']])" />

<x-mail::cancel-appointment :email="Auth::user()->email" :phone="$event['user_phone']" />

<x-mail::no-reply />

</x-mail::message>
