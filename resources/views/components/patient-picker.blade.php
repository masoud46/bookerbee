@if (!isset($id))
	<p class="db-picker-component text-danger">A unique "id" attribute must be provided!</p>
@else
	<div id="{{ $id }}" class="{{ $class ?? '' }} patient-picker-component w-100 position-relative">
		<input type="hidden" class="patient-picker-autocomplete-url" value="{{ $autocompleteUrl }}">
		<input type="hidden" class="patient-picker-picked-url" value="{{ $pickedUrl }}">
		<input class="patient-picker-input form-control pe-4" autocomplete="off" autofill="off" placeholder="{{ $placeholder }}">
		<i class="{{ $icon ?? 'fas fa-magnifying-glass' }} position-absolute top-0 end-0 mt-2 me-2 pt-1 opacity-25 pe-none"></i>
		<div><small class="text-muted">{{ $helperText ?? '' }}</small></div>
	</div>
@endif
