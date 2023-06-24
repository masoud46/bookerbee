<x-mail::message>
<style>
.panel { border-color: #cc0000 !important; }
.panel-content { background-color: #fff8f8 !important; }
</style>

<x-mail::hello :name="explode(', ', $event['extendedProps']['patient']['name'])[1]" />

{{ __("Your appointment with the following details has been canceled:") }}

<x-mail::appointment
	:firstname="ucfirst(Auth::user()->firstname)"
	:lastname="strtoupper(Auth::user()->lastname)"
	:date="Carbon\Carbon::parse($event['localStart'])->translatedFormat('l j F Y')"
	:start="Carbon\Carbon::parse($event['localStart'])->translatedFormat('H:i')"
	:end="Carbon\Carbon::parse($event['localEnd'])->translatedFormat('H:i')"
/>


<x-mail::no-reply />

</x-mail::message>
