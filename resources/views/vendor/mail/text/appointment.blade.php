@php
    $location = $address['line1'];
    if ($address['line2']) $location .= ', ' . $address['line2'];
    if ($address['line3']) $location .= ', ' . $address['line3'];
    $location .= ', ' . $address['code'] . ' ' . $address['city'];
    $location .= ', ' . $address['country'];
@endphp
{{ __("Practitioner:") }}: {{ $lastname }}, {{ $firstname }}
{{ __("Date:") }}: {{ $date }}
{{ __("Time:") }}: {{ $time }}
{{ __("Duration:") }}: {{ $duration }} {{ __('minutes') }}
{{ __("Address:") }}: {{ $location }}

@if (isset($message))
{{ $message }}
@endif
