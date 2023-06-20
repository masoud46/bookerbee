@props([
    'firstname',
    'lastname',
    'date',
    'start',
    'end',
])
<x-mail::panel>
<table>
<tr>
<td style="text-align: right; font-size: 0.9em; font-style: italic;">{{ __("Practitioner:") }}</td>
<td>{{ $lastname }}, {{ $firstname }}</td>
</tr>
<tr>
<td style="text-align: right; font-size: 0.9em; font-style: italic;">{{ __("Date:") }}</td>
<td>{{ $date }}</td>
</tr>
<tr>
<td style="text-align: right; font-size: 0.9em; font-style: italic;">{{ __("Start:") }}</td>
<td>{{ $start }}</td>
</tr>
<tr>
<td style="text-align: right; font-size: 0.9em; font-style: italic;">{{ __("End:") }}</td>
<td>{{ $end }}</td>
</tr>
</table>
</x-mail::panel>
