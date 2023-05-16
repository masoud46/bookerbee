@php
	$domain = request()->getHttpHost();
	$resources = ['resources/scss/app.scss', 'resources/js/app.js'];
	
	if (in_array($domain, config('project.external_domains', []))) {
	    $path = "templates/{$domain}";

	    if (file_exists(resource_path() . "/{$path}/scss/app.scss")) {
	        $resources = ["resources/{$path}/scss/app.scss", 'resources/js/app.js'];
	    }

	    if (file_exists(resource_path() . "/{$path}/js/app.js")) {
	        $resources[] = "resources/{$path}/js/app.js";
	    }
	}
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
{{-- <html lang="{{ LaravelLocalization::getCurrentLocale() }}"> --}}

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- CSRF Token for AJAX calls -->
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>{{ config('app.name', 'Laravel') }}</title>

	@include('layouts.fonts')
	<link rel="stylesheet" href="{{ asset('/build/fonts/fontawesome/css/all.min.css') }}">

	<script>
		/* Pass php variables to javascript */
		window.laravel = {
			locale: '{{ LaravelLocalization::getCurrentLocale() }}',
			// TODO: To show a message if the content might need to be saved
			modified: false,
		}
		window.laravel.messages = {
			unexpectedError: `{{ __('An unexpected error has occurred.') }}<br>{{ __('Try again.') }}`,
			databaseError: `{{ __('Changes could not be applied.') }}<br>{{ __('Try again.') }}`,
			irreversibleAction: `{{ __('This action is irreversible!') }}<br>{{ __('Do you want to continue?') }}`,
			saveModification: `{{ __('Changes are not saved.') }}<br>{{ __('Do you want to continue?') }}`,
			modificationSaved: `{{ __('Changes have been saved.') }}`,
		}
	</script>

	<!-- Scripts -->
	@vite($resources)

	@stack('assets')

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

<body class="@yield('body-class')">
	<div id="app">
		<nav class="navbar navbar-expand navbar-light bg-white shadow-sm">
			<div class="container">
				{{-- <a class="navbar-brand" href="{{ url('/') }}">
					{{ config('app.name', 'Laravel') }}
				</a> --}}
				<h5 class="navbar-brand mb-0">{!! $page_title ?? config('app.name', 'Laravel') !!}</h5>
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
					<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse" id="navbarSupportedContent">
					<!-- Left Side Of Navbar -->
					<ul class="navbar-nav me-auto"></ul>

					<!-- Right Side Of Navbar -->
					<ul class="navbar-nav ms-auto">
						{{-- <li class="nav-item">
							<a class="nav-link" href="{{ route('email.change-email') }}">Send ChangeEmail Mail</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="{{ route('email.change-password') }}">Send ChangePassword Mail</a>
						</li> --}}
						<li class="nav-item">
							<a class="nav-link" href="{{ route('home') }}">{{ __('Statement') }}</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="{{ route('patient.index') }}">{{ __('Patient') }}</a>
						</li>
						{{-- <li class="nav-item">
							<a class="nav-link" href="{{ route('agenda.index') }}">{{ __('Agenda') }}</a>
						</li> --}}
						<li class="nav-item dropdown">
							<a id="navbarDropdown" class="nav-link dropdown-toggle" href="/#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
								<i class="fa-solid fa-bars fa-fw"></i>
							</a>
							<div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="navbarDropdown">
								<div id="navbarDropdown" class="dropdown-item disabled xborder-bottom fst-italic fw-bold xpt-1">
									{{ Auth::user()->firstname }} {{ Auth::user()->lastname }}
								</div>
								<hr class="my-2">
								<a class="dropdown-item" href="{{ route('profile') }}">
									<i class="far fa-user fa-fw me-1"></i> {{ __('My information') }}
								</a>
								<a class="dropdown-item" href="{{ route('settings') }}">
									<i class="fas fa-sliders fa-fw me-1"></i> {{ __('Settings') }}
								</a>
								<hr class="my-2">
								<a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
									<i class="fa-solid fa-arrow-right-from-bracket fa-fw text-danger me-1"></i> {{ __('Logout') }}
								</a>
								<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
									@csrf
								</form>
							</div>
						</li>
						@php($locales = LaravelLocalization::getSupportedLocales())
						@if (count($locales) > 1)
							<li class="nav-item dropdown">
								<a id="navbarLangDropdown" class="nav-link dropdown-toggle font-monospace" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-language me-1"></i><small>{{ strtoupper(substr(LaravelLocalization::getCurrentLocaleName(), 0, 2)) }}</small></a>
								<div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="navbarLangDropdown">
									@foreach ($locales as $localeCode => $properties)
										<a class="dropdown-item" rel="alternate" hreflang="{{ $localeCode }}" href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
											{{ $properties['native'] }}
										</a>
									@endforeach
								</div>
							</li>
						@endif
					</ul>
				</div>
			</div>
		</nav>

		<main class="pb-4">
			@yield('content')
		</main>

	</div>

	@yield('modals')

	<div id="yes-no-modal" class="modal fade" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content shadow">
				<div class="modal-header shadow-sm text-bg-warning">
					<h6 class="modal-title" id="yes-no-modal-title">{{ __('Confirmation') }}</h6>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body px-4" tabindex="-1"></div>
				<div class="modal-footer">
					<button type="button" class="btn btn-sm btn-outline-dark btn-yes">{{ __('Yes') }}</button>
					<button type="button" class="btn btn-sm btn-outline-dark btn-no">{{ __('No') }}</button>
				</div>
			</div>
		</div>
	</div>

	<div id="flash-message" class="rounded shadow flash-message">
		<div class="flash-message-text">Flash | Message</div>
		<div class="flash-message-close" onclick="this.parentElement.classList.remove('flash-message-visible')"><i class="fas fa-times"></i></div>
	</div>

	<div class="body-overlay"></div>

	@stack('scripts')

</body>

</html>
