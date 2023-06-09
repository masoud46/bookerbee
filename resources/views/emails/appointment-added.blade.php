<x-mail::message>
<style>
.panel { font-size: 16px; }
img[alt="EmailIcon"], img[alt="PhoneIcon"] { width: 16px; }
</style>

# {{ __('Hello :name', ['name' => explode(', ', $event['extendedProps']['patient']['name'])[1]]) }},

{{ __("We confirm your appointment with the following details:") }}

<x-mail::panel>
<table>
<tr>
<td style="text-align: right;">{{ __("Practitioner:") }}</td>
<td style="font-weight: bold;">{{ strtoupper(Auth::user()->lastname) }}, {{ ucfirst(Auth::user()->firstname) }}</td>
</tr>
<tr>
<td style="text-align: right;">{{ __("Date:") }}</td>
<td style="font-weight: bold;">{{ Carbon\Carbon::parse($event['localStart'])->translatedFormat('l j F Y') }}</td>
</tr>
<tr>
<td style="text-align: right;">{{ __("Start:") }}</td>
<td style="font-weight: bold;">{{ Carbon\Carbon::parse($event['localStart'])->translatedFormat('H:i') }}</td>
</tr>
<tr>
<td style="text-align: right;">{{ __("End:") }}</td>
<td style="font-weight: bold;">{{ Carbon\Carbon::parse($event['localEnd'])->translatedFormat('H:i') }}</td>
</tr>
</table>
</x-mail::panel>

<p>
{{ __('You can import this appointment into your personal calendar by clicking on the button below.') }}
</p>

<x-mail::button :url="route('event.export', ['id' => $event['hash_id']])">
{{ __('Import the appointment') }}
</x-mail::button>

<p style="margin-bottom: 8px;">
{{ __("If you wish to cancel this appointment, contact the practitioner directly:") }}<br>
</p>

<table style="font-style: italic;">
<tbody>
<tr>
<td style="padding-right: 8px; opacity: 0.5;">
<img src="{{ asset('build/images/envelope.png') }}" alt="EmailIcon">
</td>
<td>
<a href="mailto:{{ Auth::user()->email }}" style="font-size: 16px;">{{ Auth::user()->email }}</a>
</td>
</tr>
<tr>
<td style="padding-right: 8px; opacity: 0.5;">
<img src="{{ asset('build/images/phone.png') }}" alt="PhoneIcon">
</td>
<td>
<a href="tel:{{ $event['user_phone'] }}" style="font-size: 16px;">{{ $event['user_phone'] }}</a>
</td>
</tr>
</tbody>
</table>

<p style="margin-top: 20px; margin-bottom: 0;">
{{ __('Thanks') }},<br>
{{ config('app.name') }}
</p>
</x-mail::message>
