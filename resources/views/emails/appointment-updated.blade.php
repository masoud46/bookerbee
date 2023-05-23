@php
    Carbon\Carbon::setlocale(config('app.locale'));
@endphp
<x-mail::message>
# {{ __('Hello :name', ['name' => explode(', ', $event['extendedProps']['patient']['name'])[1]]) }},

{!! __('Your appointment with your practitioner, :firstname :lastname, has been rescheduled. Please take note of the new schedule below.', ['firstname' => '<strong>'.Auth::user()->firstname, 'lastname' => Auth::user()->lastname.'</strong>']) !!}

<div style="margin-top: 30px;">{{ __('New schedule') }}</div>
<div style="margin-top: 5px; padding: 15px 20px; background-color: #edf2f7; border: 1px solid #d8e3ee; border-radius: 4px;">
	<table>
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
</div>

{{-- <div style="margin-top: 30px;  padding: 15px 20px; background-color: rgba(255, 0, 0, 0.06); border-radius: 4px;"> --}}
<div style="margin-top: 30px; xcolor: #cc6666; font-style: italic;">
	{!! __('For your information: the initial schedule was planned for :date, from :start to :end.', [
		'date' => '<span">'.Carbon\Carbon::parse($old_event['localStart'])->translatedFormat('l j F Y').'</span>',
		'start' => '<span">'.Carbon\Carbon::parse($old_event['localStart'])->translatedFormat('H:i').'</span>',
		'end' => '<span">'.Carbon\Carbon::parse($old_event['localEnd'])->translatedFormat('H:i').'</span>',
	]) !!}
</div>

<div style="margin-top: 30px;">
	{{ __('Thanks') }},<br>
	{{ config('app.name') }}
</div>
</x-mail::message>
