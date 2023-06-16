@php
	$domain = request()->getHttpHost();
	$external_domain = in_array($domain, config('project.external_domains', []));
	$resources = ['resources/scss/auth.scss', 'resources/js/auth.js'];
	
	if ($external_domain) {
	    $path = "templates/{$domain}";
	
	    if (file_exists(resource_path() . "/{$path}/scss/auth.scss")) {
	        $resources = ["resources/{$path}/scss/auth.scss", 'resources/js/auth.js'];
	    }
	
	    if (file_exists(resource_path() . "/{$path}/js/auth.js")) {
	        $resources[] = "resources/{$path}/js/auth.js";
	    }
	}
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>{{ config('app.name', 'BookerBee') }}</title>

	<link rel="icon" sizes="192x192" href="{{ asset('build/images/favicon.png') }}">

	<!-- Scripts -->
	@vite($resources)

	@include('layouts.fonts')

</head>

<body>
	<div id="app">
		<div class="menu-bar d-flex p-3">
			<div class="flex-grow-1">
				@if (!$external_domain)
					<div class="internal-domain d-flex">
						<img class="domain-logo" src="{{ asset('build/images/logo.svg') }}" alt="logo">
					</div>
				@endif
			</div>
			<div>
				@if (!Route::is('login'))
					<a class="fw-bold me-2" href="{{ route('login') }}">{{ __('Login') }}</a>
				@endif
				@php($locales = LaravelLocalization::getSupportedLocales())
				@if (count($locales) > 1)
					@foreach ($locales as $localeCode => $properties)
						<a type="button" class="btn-locale btn btn-sm btn-outline-primary fw-bold px-1 py-0 m-1 {{ $localeCode === App::getLocale() ? 'active-locale' : '' }}" rel="alternate" hreflang="{{ $localeCode }}" href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
							{{ strtoupper($localeCode) }}
						</a>
					@endforeach
				@endif
			</div>
		</div>

		@yield('content')

	</div>

</body>

</html>
