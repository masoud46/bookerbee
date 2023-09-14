<x-mail::message>

<x-mail::hello :name="ucfirst(trim(explode(', ', $event['extendedProps']['patient']['name'])[1]))" />

{{ __("Your appointment with the following details has been confirmed:") }}

<x-mail::appointment
	:firstname="ucfirst(Auth::user()->firstname)"
	:lastname="strtoupper(Auth::user()->lastname)"
	:date="Carbon\Carbon::parse($event['localStart'])->translatedFormat('l j F Y')"
	:time="Carbon\Carbon::parse($event['localStart'])->translatedFormat('H:i')"
	:duration="$event['duration'] . ' ' . __('minutes')"
	:address="$event['address']"
	:message="isset($event['msg_email']) ? $event['msg_email'] : null"
/>

<x-mail::import-appointment :url="route('event.export', ['id' => $event['hash_id']])" />

<x-mail::cancel-appointment :email="Auth::user()->email" :phone="$event['user_phone']" />

<x-mail::no-reply />

</x-mail::message>
