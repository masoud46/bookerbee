<x-mail::message>
<style>
	.panel {
		border-color: #cc0000 !important;
	}
	.panel-content {
		background-color: #fff4f4 !important;
	}
</style>

# {{ __('Hello :name', ['name' => explode(', ', $event['extendedProps']['patient']['name'])[1]]) }},

{{ __("Your appointment with the following details has been cancelled:") }}

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

<p style="margin-bottom: 0;">
{{ __('Thanks') }},<br>
{{ config('app.name') }}
</p>
</x-mail::message>
