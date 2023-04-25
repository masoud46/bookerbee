<x-mail::message>
# Introduction

# Hi!

## Hello Mr/Mrs {{$name}}
This is the example of markdown email

<x-mail::button :url="''">
Button Text
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
