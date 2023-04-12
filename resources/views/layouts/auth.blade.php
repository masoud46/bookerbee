<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>{{ config('app.name', 'Laravel') }}</title>

	<!-- Scripts -->
	@vite('resources/js/auth.js')

	@if (session()->has('success') || session()->has('error'))
		<script>
			const httpFlashMessage = {
				message: JSON.stringify("{{ session('success') ?? session('error') }}"),
				error: {{ session()->has('error') ? 'true' : 'false' }},
			}
			httpFlashMessage.message = httpFlashMessage.message.substring(1, httpFlashMessage.message.length - 1)
		</script>
	@endif

</head>

<body>
	<div id="app">
		<div class="position-fixed top-0 start-0 end-0 text-end p-3">
			@if (!Route::is('login'))
				<a class="fw-bold me-2" href="{{ route('login') }}">{{ __('Login') }}</a>
			@endif
			@php($locales = LaravelLocalization::getSupportedLocales())
			@if (count($locales) > 1)
				@foreach ($locales as $localeCode => $properties)
					<a type="button" class="btn-locale btn btn-sm btn-primary fw-bold px-1 py-0 m-1 {{ $localeCode === App::getLocale() ? 'active-locale' : '' }}" rel="alternate" hreflang="{{ $localeCode }}" href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
						{{ strtoupper($localeCode) }}
					</a>
				@endforeach
			@endif
		</div>

		@yield('content')

	</div>

</body>

</html>
