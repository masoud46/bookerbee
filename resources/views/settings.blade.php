@extends('layouts.app', ['page_title' => '<i class="fas fa-sliders me-2"></i>' . __("Settings")])

@php($default_country_code = config('project.default_country_code'))

@section('content')
	<div class="container">
		<form id="settings-form" method="post" action="{{ route('settings.update') }}" class="form" autocomplete="off" autofill="off">
			@method('put')
			@csrf
			<input type="hidden" id="settings-saved">
			<div class="form-group row mt-4">
				<div class="col-sm-4 col-md-3 col-lg-2 mb-3">
					<label for="settings-amount" class="col-12 col-form-label"><span>{{ __("Default amount") }}</span></label>
					<div class="input-group @error('settings-amount') has-validation @enderror">
						<input id="settings-amount" name="settings-amount" class="form-control form-control @error('settings-amount') is-invalid @enderror" aria-describedby="input-group-sizing" placeholder="{{ __("Amount") }}" value="{{ old('settings-amount', $settings->amount) }}">
						<span class="input-group-text" id="input-group-sizing">€</span>
						@error('settings-amount')
							<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-sm-8 col-md-7 col-lg-5 col-xl-4 mb-3">
					<label for="settings-location" class="col-12 col-form-label"><span>{{ __("Default location") }}</span></label>
					<select id="settings-location" name="settings-location" class="form-select @error('settings-location') is-invalid @enderror">
						<option value="" selected hidden>{{ __("Location") }}</option>
						@foreach ($locations as $location)
							<option value="{{ $location->id }}" {{ old('settings-location') == $location->id || $settings->location === $location->id ? 'selected' : '' }}>{{ $location->code }} - {{ $location->description }}</option>
						@endforeach
					</select>
					@error('settings-location')
						<div class="invalid-feedback">{{ $message }}</div>
					@enderror
				</div>
			</div>
			<div class="row mb-4">
				<div class="col-12">
					<div class="border-top pt-1"><small class="text-muted fst-italic">{{ __("All fields are mandatory.") }}</small></div>
				</div>
			</div>
			<div class="row my-4">
				<div class="col-12">
					<button type="submit" class="btn btn-primary btn-fa-spinner me-3" data-saved="0"><i class="icon-visible fas fa-file-arrow-down fa-fw"></i><i class="icon-hidden fas fa-spinner fa-spin fa-fw"></i> {{ __("Save") }}</button>
				</div>
				<div id="settings-not-saved-message" class="col-12 pt-1 {{ session()->has('error') ? '' : 'invisible' }}">
					<small class="text-danger">{{ __("Settings are not saved.") }}</small>
				</div>
			</div>
		</form>
	</div>
@endsection


@push('assets')
	@vite($entries)
@endpush
