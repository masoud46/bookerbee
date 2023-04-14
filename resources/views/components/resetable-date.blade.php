@php
	$resetable_date_disabled = isset($disabled) && $disabled !== '';
	
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
<div class="{{ $class ?? '' }} d-flex align-items-center">
	<input type="date" id="{{ $inputId }}" name="{{ $inputName }}" class="form-control {{ $small }} flex-grow-1 resetable-date-input" {{ $resetable_date_disabled ? 'disabled' : '' }} value="{{ $inputValue }}">
	@if (!$resetable_date_disabled)
		<div class="rounded text-muted ms-1 resetable-date-button" onclick="
			const input = this.parentElement.querySelector('input')
			if (input.value) {
				input.value = null
				input.dispatchEvent(new Event('input'))
			}
		">
			<i class="fas fa-arrows-rotate"></i>
		</div>
	@endif
</div>
