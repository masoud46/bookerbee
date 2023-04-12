@php($resetable_date_disabled = isset($disabled)  && $disabled !== '')
<div class="{{ $class }} d-flex align-items-center">
	<input type="date" id="{{ $inputId }}" name="{{ $inputName }}" class="form-control form-control-sm flex-grow-1 resetable-date-input" {{ $resetable_date_disabled ? 'disabled' : '' }} value="{{ $inputValue }}">
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
