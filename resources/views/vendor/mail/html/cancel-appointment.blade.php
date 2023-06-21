@props([
    'email',
    'phone',
])
<p style="margin-bottom: 4px;">
{{ __("If you wish to cancel this appointment, contact the practitioner directly:") }}<br>
</p>

<table style="margin-bottom: 20px;" cellpadding="0" cellspacing="0" role="presentation">
<tbody>
<tr>
<td style="padding-top: 6px; padding-bottom: 6px; padding-right: 10px; opacity: 0.5;">
<img src="{{ asset('build/images/envelope.png') }}" alt="EmailIcon" width="20">
</td>
<td style="padding-bottom: 6px;">
<a href="mailto:{{ $email }}" style="font-size: 16px;">{{ $email }}</a>
</td>
</tr>
<tr>
<td style="padding-top: 4px; padding-right: 10px; opacity: 0.5;">
<img src="{{ asset('build/images/phone.png') }}" alt="PhoneIcon" width="20">
</td>
<td>
<a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" style="font-size: 16px;">{{ $phone }}</a>
</td>
</tr>
</tbody>
</table>
