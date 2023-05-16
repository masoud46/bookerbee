@if (!isset($id))
	<p class="db-picker-component text-danger">A unique "id" attribute must be provided!</p>
@else
	@php
		$small = null;
		$small_class = 'form-control-sm';
		
		if (isset($class)) {
		    $class = preg_replace('/\s+/', ' ', trim($class));
		    $class_array = explode(' ', $class);
		
		    if (in_array($small_class, $class_array)) {
		        $small = $small_class;
		        $class_array = array_filter($class_array, function ($var) use ($small_class) {
		            return $var !== $small_class;
		        });
		    }
		
		    $class = implode(' ', $class_array);
		}
	@endphp

	<div id="{{ $id }}" class="{{ $class ?? '' }} patient-picker-component w-100 position-relative">
		<input type="hidden" class="patient-picker-autocomplete-url" value="{{ $autocompleteUrl }}">
		<input type="hidden" class="patient-picker-picked-url" value="{{ $pickedUrl ?? null }}">
		<input class="patient-picker-input form-control {{ $small }} pe-4" autocomplete="off" autofill="off" placeholder="{{ $placeholder }}">
		<i class="{{ $icon ?? 'fas fa-magnifying-glass' }} position-absolute top-0 end-0 {{ $small ? 'mt-1' : 'mt-2' }} me-2 pt-1 opacity-25 pe-none"></i>
		<div><small class="text-muted">{{ $helperText ?? '' }}</small></div>
	</div>
@endif
