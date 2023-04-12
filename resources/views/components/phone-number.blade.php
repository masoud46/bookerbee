@if (!isset($id))
	<p class="db-picker-component text-danger">A unique "id" attribute must be provided!</p>
@elseif ($id === $numberField)
	<p class="db-picker-component text-danger">The "id" and "numberField" attributes must be different!</p>
@else
	@php
		$country_id = isset($country) ? intval(trim($country)) : 0;
		
		$list = $countries->keyBy('code');
		$defaultCountryId = $list->get($defaultCountryCode)->id;
		$country_id = $country_id > 0 ? $country_id : $defaultCountryId;
		$list = $list->keyBy('id');
		
		$code = $list->get($country_id)->code;
		$prefix = $list->get($country_id)->prefix;
		
		$small = isset($small) && strtolower($small) === 'true';
		
		// set input's error class and prevent focus on parent (.is-invalid-parent)
$error_class_name = 'is-invalid';
if (isset($class)) {
    $class = preg_replace('/\s+/', ' ', trim($class));
    $class_array = explode(' ', $class);
    if (in_array($error_class_name, $class_array)) {
        $error_class = $error_class_name;
        $class_array[] = 'is-invalid-parent'; // to prevent focus
        $class = implode(' ', $class_array);
		    }
		}
	@endphp

	<div id="{{ $id }}" class="phone-fax-number-component {{ $class ?? '' }}" data-default-country-id="{{ $defaultCountryId }}" data-default-country-code="{{ $defaultCountryCode }}">
		<input type="hidden" name="{{ $countryField }}" class="phone-number-country" value="{{ $country_id ?? '' }}">
		<div class="input-group {{ isset($error_class) ? 'has-validation' : '' }}">
			<button type="button" class="phone-number-dropdown btn  {{ $small ? 'btn-sm' : '' }} btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
				<span class="fib fi-{{ strtolower($code) }}"></span>
			</button>
			<div class="dropdown-menu shadow pt-0">
				<div class="d-flex flex-column h-100 overflow-hidden">
					<div class="position-relative p-2">
						<input class="dropdown-search form-control form-control-sm pe-4" placeholder="{{ __('Search') }}" />
						<i class="fas fa-magnifying-glass position-absolute opacity-25 top-0 end-0 mt-3 me-3 pe-none"></i>
					</div>
					<div class="dropdown-items flex-grow-1 mb-0" tabindex="-1">
						@php($transliterator = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;', Transliterator::FORWARD))
						@foreach ($list as $value)
							<button type="button" class="dropdown-item {{ $value->code === $code ? 'active' : '' }}" data-id="{{ $value->id }}" data-code="{{ strtolower($value->code) }}" data-name="{{ $transliterator->transliterate($value->name) }}" data-prefix="{{ $value->prefix }}">
								<span class="fib fi-{{ strtolower($value->code) }} me-1"></span> {{ $value->name }} <span class="country-prefix">{{ $value->prefix }}</span>
							</button>
						@endforeach
					</div>
				</div>
			</div>
			<div class="phone-number-prefix form-control {{ $small ? 'form-control-sm' : '' }} d-flex justify-content-end align-items-center user-select-none">{{ $prefix }}</div>
			<input type="text" name="{{ $numberField }}" class="{{ $error_class ?? '' }} phone-number-input form-control {{ $small ? 'form-control-sm' : '' }}" value="{{ $number ?? '' }}">
		</div>
		{{-- Load ahead all the needed flag icons, for the dropdown list --}}
		<div class="phone-number-flag-icons d-none">
			@foreach ($countries as $country)
				<span class="fi fi-{{ strtolower($country->code) }}"></span>
			@endforeach
		</div>
	</div>
@endif
