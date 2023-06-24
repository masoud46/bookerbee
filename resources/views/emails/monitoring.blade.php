<x-mail::message>

# {{ $message }}

@if ($link)
<a href="{{ $link }}">{{ $link }}</a>	
@endif

<x-mail::no-reply />

</x-mail::message>
