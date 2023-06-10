@props([
    'email',
    'phone',
])
<p style="margin-bottom: 4px;">
{{ __("If you wish to cancel this appointment, contact the practitioner directly:") }}<br>
</p>

<table style="font-style: italic; line-height: 1.8; margin-bottom: 20px;" cellpadding="0" cellspacing="0" role="presentation">
<tbody>
<tr>
<td style="padding-right: 15px; opacity: 0.5;">
<img src="{{ asset('build/images/envelope.png') }}" alt="EmailIcon">
</td>
<td>
<a href="mailto:{{ $email }}" style="font-size: 16px;">{{ $email }}</a>
</td>
</tr>
<tr>
<td style="padding-right: 15px; opacity: 0.5;">
<img src="{{ asset('build/images/phone.png') }}" alt="PhoneIcon">
</td>
<td>
<a href="tel:{{ $phone }}" style="font-size: 16px;">{{ $phone }}</a>
</td>
</tr>
</tbody>
</table>
