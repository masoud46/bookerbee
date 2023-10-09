{{ $actionText = __('Confirm the email address') }}
<x-mail::message>

<x-mail::hello :name="ucfirst(Auth::user()->firstname)" />

{{ __("You are receiving this email because we received an email address change request for your account.") }}

{{ __("Please click the button below to confirm your new email address.") }}

<x-mail::button :url="$url">
{{ $actionText }}
</x-mail::button>

{{ __("If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:", ['actionText' => $actionText]) }}
<a href="{{ $url }}">{{ $url }}</a>

{{ __("This email address confirmation link will expire in :count minutes.", ['count' => $timeout]) }}

{{ __("If you did not request an email address change, no further action is required.") }}

<x-mail::no-reply />

</x-mail::message>
