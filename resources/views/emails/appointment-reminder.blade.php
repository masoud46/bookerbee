@php
    Carbon\Carbon::setlocale(config('app.locale'));
	$start = Carbon\Carbon::parse($event['start'])->setTimezone($event['timezone']);
	$end = Carbon\Carbon::parse($event['end'])->setTimezone($event['timezone']);
@endphp
<x-mail::message>
<style>
.panel { font-size: 16px; }
</style>

# {{ __('Hello :name', ['name' => $event['patient_firstname']]) }},

{{ __('Your next appointment with the following details, will start in about :time hours.', ['time' => config('project.reminder_email_time')]) }}

<x-mail::panel>
<table>
<tr>
<td style="text-align: right;">{{ __("Practitioner:") }}</td>
<td style="font-weight: bold;">{{ strtoupper($event['user_lastname']) }}, {{ ucfirst($event['user_firstname']) }}</td>
</tr>
<tr>
<td style="text-align: right;">{{ __("Date:") }}</td>
<td style="font-weight: bold;">{{ $start->translatedFormat('l j F Y') }}</td>
</tr>
<tr>
<td style="text-align: right;">{{ __("Start:") }}</td>
<td style="font-weight: bold;">{{ $start->translatedFormat('H:i') }}</td>
</tr>
<tr>
<td style="text-align: right;">{{ __("End:") }}</td>
<td style="font-weight: bold;">{{ $end->translatedFormat('H:i') }}</td>
</tr>
</table>
</x-mail::panel>

<p style="margin-bottom: 0;">
{{ __('Thanks') }},<br>
{{ config('app.name') }}
</p>
</x-mail::message>
