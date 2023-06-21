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
<td style="text-align: right;">{{ __("Practitioner:") }}</td>
<td style="font-weight: bold;">{{ $lastname }}, {{ $firstname }}</td>
</tr>
<tr>
<td style="text-align: right;">{{ __("Date:") }}</td>
<td style="font-weight: bold;">{{ $date }}</td>
</tr>
<tr>
<td style="text-align: right;">{{ __("Start:") }}</td>
<td style="font-weight: bold;">{{ $start }}</td>
</tr>
<tr>
<td style="text-align: right;">{{ __("End:") }}</td>
<td style="font-weight: bold;">{{ $end }}</td>
</tr>
</table>
</x-mail::panel>
