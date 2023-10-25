@php
    $location = '';
    if (isset($address['line1']) && $address['line1']) $location = $address['line1'];
    if (isset($address['line2']) && $address['line2']) $location .= "<br>{$address['line2']}";
    if (isset($address['line3']) && $address['line3']) $location .= "<br>{$address['line3']}";
    if (
        isset($address['code']) && $address['code'] &&
        isset($address['city']) && $address['city'] &&
        isset($address['country']) && $address['country']
    )
        $location .= "<br>{$address['code']} {$address['city']}<br>{$address['country']}";
@endphp
{{ __("Practitioner:") }}: {{ $lastname }}, {{ $firstname }}
{{ __("Date:") }}: {{ $date }}
{{ __("Time:") }}: {{ $time }}
{{ __("Duration:") }}: {{ $duration }} {{ __('minutes') }}
{{ __("Address:") }}: {{ $location }}

@if (isset($message))
{{ $message }}
@endif
