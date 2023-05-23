@extends('layouts.app', ['page_title' => '<i class="fas fa-rectangle-list me-2"></i>' . __('Statement')])

@php
	$default_country_code = config('project.default_country_code');
	$limits = config('project.load_invoice_limits');
	$default_limit = $limits[0];
	$limit = intval(request()->route('limit') ?? $default_limit);
	$menu_items = [];
	
	foreach ($limits as $months) {
	    $menu_items[$months] = [
	        'title' => __('The last :months months', ['months' => $months]),
	        'href' => route('invoice.index', ['limit' => $months]),
	    ];
	}
	
	foreach ($years as $year) {
	    $menu_items[$year->year] = [
	        'title' => $year->year,
	        'href' => route('invoice.index', ['limit' => $year->year]),
	    ];
	}

	$menu_items['0'] = [
		'title' => __('All the invoices'),
		'href' => route('invoice.index', ['limit' => 'all']),
	];
@endphp

@section('content')
	<input type="hidden" id="invoice-show-url" value="{{ route('invoice.show', ['invoice' => '?id']) }}">
	<input type="hidden" id="invoice-print-url" value="{{ route('invoice.print', ['invoice' => '?id']) }}">
	<div class="container">
		<div class="row">
			<div id="patient-picker" class="col-md-6 my-4">
				<h4 class="border-bottom pb-2">{{ __('New invoice') }}</h4>
				<label class="col-12 col-form-label position-relative">
					{{ __('Patient') }}
					<small class="position-absolute bottom-0 end-0 me-2"><span class="patient-count me-1">0</span> / {{ $patients_count }}</small>
				</label>
				<div class="text-end pe-2"></div>
				<x-patient-picker id="patient-picker-component" autocompleteUrl="{{ route('patient.autocomplete') }}" picked-url="{{ route('invoice.new', ['patient' => '?id']) }}" placeholder="{{ __('Last name / First name / Reg. number') }}" helper-text="{{ __('Start by typing three characters.') }}" />
			</div>
		</div>
		<div id="items-table-filter" class="row justify-content-between">
			<div class="col-12 mt-4">
				<h4 class="border-bottom pb-2">{{ __('Invoices') }}</h4>
			</div>
			<div class="col-md-6 col-lg-7 col-xl-8 mt-2">
				<span class="dropdown">
					<button class="btn xbtn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
						{{ $menu_items[$limit]['title'] }}
					</button>
					<ul class="dropdown-menu shadow">
						@foreach ($menu_items as $key => $value)
							@if ($key == 0)
								<hr class="my-2">
							@endif
							<li><a href="{{ $value['href'] }}" type="button" class="dropdown-item {{ $key === $limit ? 'text-bg-secondary opacity-75 disabled' : '' }}">{{ $value['title'] }}</a></li>
						@endforeach
					</ul>
				</span>
				<small class="h-100 d-flex align-items-center text-nowrap float-end"><span class="items-table-count me-1">{{ $invoices->count() }}</span>/<span class="items-table-total ms-1">{{ $invoices->count() }}</span></small>
			</div>
			<div class="col-md-6 col-lg-5 col-xl-4 mt-3 mt-sm-2 d-flex">
				<input class="items-table-filter-input form-control" placeholder="{{ __('Search') }}" value="">
			</div>
		</div>
		<div id="invoices-container" class="row mt-3">
			<div class="col-12 table-responsive">
				<table class="table table-sm table-striped table-hover">
					<thead>
						<tr>
							<th scope="col">{{ __('Ref.') }}</th>
							<th scope="col">{{ __('Date') }}</th>
							<th scope="col">{{ __('Patient') }}</th>
							<th scope="col">{{ __('Covered patient') }}</th>
							<th scope="col">{{ __('Amount') }}</th>
							<th scope="col"></th>
						</tr>
					</thead>
					<tbody class="bg-white">
						@foreach ($invoices as $invoice)
							<tr class="items-table-item user-select-none {{ $invoice->patient_category === 1 ? 'national-healthcare-item' : '' }} {{ $invoice->active ? '' : 'inactive-invoice' }}" role="button" data-id="{{ $invoice->id }}">
								{{-- <td scope="col" class="invoice-item-reference"><i class="fas fa-check text-success me-2 {{ $invoice->active ? '' : 'invisible' }}"></i>{{ $invoice->reference }}<span>{{ $invoice->patient_category === 1 ? 'CNS' : '' }}</span></td> --}}
								<td scope="col" class="invoice-item-reference">{{ $invoice->reference }}<span>{{ $invoice->patient_category === 1 ? 'CNS' : '' }}</span></td>
								<td scope="col">{{ $invoice->date }}</td>
								<td scope="col">{{ $invoice->name }}</td>
								<td scope="col">{{ $invoice->patient }}</td>
								<td scope="col">{{ $invoice->total }} €</td>
								<td scope="col" class="invoice-item-print">
									<a class="float-end" href="{{ route('invoice.print', ['invoice' => $invoice->id]) }}" target="_blank" class="float-end" title=" {{ __('Print') }} "><i class="fas fa-print pe-none"></i></a>
									{{-- <a href="{{ route('invoice.show', ['invoice' => $invoice->id]) }}" class="float-end me-3" title=" {{ $invoice->editable ? __("Edit") : __("View") }} "><i class="far {{ $invoice->editable ? 'fa-pen-to-square' : 'fa-eye' }} fa-fw pe-none"></i></a> --}}
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>
	</div>
@endsection

@push('assets')
	@vite($entries)
@endpush
