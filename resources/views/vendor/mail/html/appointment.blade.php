@props([
    'firstname',
    'lastname',
    'date',
    'time',
    'duration',
    'address',
    'message',
])
@php
    $location = $address['line1'];
    if ($address['line2']) $location .= '<br>' . $address['line2'];
    if ($address['line3']) $location .= '<br>' . $address['line3'];
    $location .= '<br>' . $address['code'] . ' ' . $address['city'];
    $location .= '<br>' . $address['country'];
@endphp
<x-mail::panel>
<table>
<tr>
<td align="right" valign="top" style="text-align: right; vertical-align: top;">{{ __("Practitioner:") }}</td>
<td style="font-weight: bold;">{{ $lastname }}, {{ $firstname }}</td>
</tr>
<tr>
<td align="right" valign="top" style="text-align: right; vertical-align: top;">{{ __("Date:") }}</td>
<td style="font-weight: bold;">{{ $date }}</td>
</tr>
<tr>
<td align="right" valign="top" style="text-align: right; vertical-align: top;">{{ __("Time:") }}</td>
<td style="font-weight: bold;">{{ $time }}</td>
</tr>
<tr>
<td align="right" valign="top" style="text-align: right; vertical-align: top;">{{ __("Duration:") }}</td>
<td style="font-weight: bold;">{{ $duration }}</td>
</tr>
<tr>
<td align="right" valign="top" style="text-align: right; vertical-align: top;">{{ __("Address:") }}</td>
<td style="font-weight: bold;">{!! $location !!}</td>
</tr>
@if (isset($message))
<tr>
<td colspan="2" style="padding-top: 20px; color: #c00000;">{!! nl2br(strip_tags($message)) !!}</td>
</tr>
@endif
</table>
</x-mail::panel>
