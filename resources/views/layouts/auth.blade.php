@php
	$domain = request()->getHttpHost();
	$external_domain = in_array($domain, config('project.external_domains', []));
	$resources = ['resources/scss/auth.scss', 'resources/js/auth.js'];
	$favicon = 'resources/images/favicon.png';
	
	if ($external_domain) {
	    $path = "templates/{$domain}";
	
	    if (file_exists(resource_path() . "/{$path}/scss/auth.scss")) {
	        $resources = ["resources/{$path}/scss/auth.scss", 'resources/js/auth.js'];
	    }
	
	    if (file_exists(resource_path() . "/{$path}/js/auth.js")) {
	        $resources[] = "resources/{$path}/js/auth.js";
	    }
	
	    if (file_exists(resource_path() . "/{$path}/images/favicon.png")) {
	        $favicon = "resources/{$path}/images/favicon.png";
	    }
	}
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">

	<title>{{ config('app.name', 'BookerBee') }}</title>

	<link rel="icon" sizes="192x192" href="{{ asset('build/images/favicon.png') }}">
	{{-- <link rel="icon" sizes="192x192" href="{{ Vite::asset($favicon) }}"> --}}

	<!-- Scripts -->
	@vite($resources)

	@if (session()->has('success') || session()->has('error'))
		@php
			$message = substr(json_encode(session('success') ?? session('error')), 1, -1);
			$type = session()->has('error') ? 'error' : 'success';
		@endphp
		<script>
			window.laravel = {
				flash: {
					message: `{{ $message }}`,
					type: '{{ $type }}',
					timeout: 10,
				}
			}
		</script>
	@endif

</head>

<body>
	<div id="app">
		<div class="menu-bar d-flex p-3">
			<div class="flex-grow-1">
				@if (!$external_domain)
					<div class="internal-domain {{ $external_domain ? 'd-none' : 'd-flex' }}">
						<img class="domain-logo" src="{{ asset('build/images/bookerbee-logo.svg') }}" alt="logo">
						{{-- <img class="domain-logo" src="{{ Vite::asset('resources/images/auth-logo.svg') }}" alt="logo"> --}}
						{{-- <img class="domain-logo" src="{{ asset('../../images/auth-logo.svg') }}" alt="logo"> --}}
						{{-- <img class="domain-logo" src="../../images/auth-logo.svg" alt="logo"> --}}
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

		<div id="flash-message" class="rounded shadow flash-message">
			<div class="flash-message-text">Flash | Message</div>
			<div class="flash-message-close" onclick="this.parentElement.classList.remove('flash-message-visible')">&times;</div>
		</div>

	</div>

	@stack('scripts')

</body>

</html>
