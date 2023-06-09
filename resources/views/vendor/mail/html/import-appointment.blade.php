@props([
    'url',
])
<p>
{{ __('You can import this appointment into your personal calendar by clicking on the button below.') }}
</p>

<x-mail::button :url="$url">
{{ __('Import the appointment') }}
</x-mail::button>
