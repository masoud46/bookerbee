<x-mail::message>
# {{ __('Hello :name', ['name' => explode(', ', $event['extendedProps']['patient']['name'])[1]]) }},

{{ __("We confirm your appointment with the details below:") }}

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
{{ __('You can import this appointment into your personal calendar by clicking on this link:') }}
<a href="{{ route('event.export', ['id' => $event['hash_id']]) }}">{{ route('event.export', ['id' => $event['hash_id']]) }}</a>
</p>

<p>
{{ __("If you wish to cancel this appointment, contact the practitioner directly.") }}
</p>

<p style="margin-bottom: 0;">
{{ __('Thanks') }},<br>
{{ config('app.name') }}
</p>
</x-mail::message>
