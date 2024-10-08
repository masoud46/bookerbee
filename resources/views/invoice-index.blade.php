@extends('layouts.app', ['page_title' => '<i class="far fa-folder-open fa-fw me-2"></i>' . __('Statements')])

@php
	$default_country_code = config('project.default_country_code');
	$limits = config('project.load_invoice_limits');
	$default_limit = $limits[0];
	$limit = intval(request()->route('limit') ?? $default_limit);
	$menu_items = [];
	
	$history_start = \Carbon\Carbon::now()
	    ->subMonth()
	    ->firstOfMonth()
	    ->format('Y-m-d');
	$history_end = \Carbon\Carbon::now()
	    ->subMonth()
	    ->lastOfMonth()
	    ->format('Y-m-d');
	
	foreach ($limits as $months) {
	    $menu_items[$months] = [
	        'title' => __('Past :months months', ['months' => $months]),
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
	    'title' => __('All the statements'),
	    'href' => route('invoice.index', ['limit' => 'all']),
	];
@endphp

@section('content')
	<input type="hidden" id="invoice-show-url" value="{{ route('invoice.show', ['invoice' => '?id']) }}">
	<input type="hidden" id="invoice-print-url" value="{{ route('invoice.print', ['invoice' => '?id']) }}">
	<div class="container">
		<div class="row">
			<div id="patient-picker" class="col-lg-6 my-4">
				<h4 class="border-bottom pb-2">{{ __('New statement') }}</h4>
				<label class="col-12 col-form-label position-relative">
					{{ __('Patient') }}
					<small class="position-absolute bottom-0 end-0 me-2"><span class="patient-count me-1">0</span> / {{ $patients_count }}</small>
				</label>
				<div class="text-end pe-2"></div>
				<x-patient-picker id="patient-picker-component" autocompleteUrl="{{ route('patient.autocomplete') }}"
					picked-url="{{ route('invoice.new', ['patient' => '?id']) }}"
					placeholder="{{ __('Last name / First name / Reg. number') }}"
					helper-text="{{ __('Start by typing three characters.') }}" />
			</div>
			<div class="col-lg-5 offset-lg-1 my-4">
				<x-invoices-report
					history-start="{{ $history_start }}"
					history-end="{{ $history_end }}"
					print-url="{{ route('invoice.report.print') }}"
					export-url="{{ route('invoice.report.export', ['start' => '?start', 'end' => '?end']) }}" />
			</div>
		</div>
		<div id="items-table-filter" class="row justify-content-between my-4">
			<div class="col-12">
				<h4 class="border-bottom pb-2">{{ __('My statements') }}</h4>
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
							<li>
								<a href="{{ $value['href'] }}" type="button" class="dropdown-item {{ $key === $limit ? 'text-bg-secondary opacity-75 disabled' : '' }}">
									{{ $value['title'] }}
								</a>
							</li>
						@endforeach
					</ul>
				</span>
				<small class="h-100 d-flex align-items-center text-nowrap float-end"><span class="items-table-count me-1">{{ $invoices->count() }}</span>/<span class="items-table-total ms-1">{{ $invoices->count() }}</span></small>
			</div>
			<div class="col-md-6 col-lg-5 col-xl-4 mt-3 mt-sm-2 d-flex search-filter position-relative">
				<input class="items-table-filter-input form-control" placeholder="{{ __('Filter') }}" value="">
				<div class="btn-search-filter position-absolute"><i class="fas fa-filter search-filter-inactive"></i><i class="fas fa-times search-filter-active"></i></div>
			</div>
		</div>
		<div id="invoices-container" class="row mt-3">
			<div class="col-12 table-responsive">
				<table class="table table-sm table-striped table-hover">
					<thead>
						<tr>
							<th scope="col">{{ __('Ref.') }}</th>
							<th scope="col">{{ __('Date') }}</th>
							<th scope="col">{{ __('Session') }}</th>
							<th scope="col">{{ __('Patient') }}</th>
							<th scope="col">{{ __('Insured') }}</th>
							<th scope="col">{{ __('Amount') }}</th>
							<th scope="col"></th>
						</tr>
					</thead>
					<tbody class="bg-white">
						@foreach ($invoices as $invoice)
							<tr class="items-table-item user-select-none {{ $invoice->patient_category === 1 ? 'national-healthcare-item' : '' }} {{ $invoice->active ? '' : 'inactive-invoice' }}" role="button" data-id="{{ $invoice->id }}">
								<td scope="col" class="invoice-item-reference">
									{{ $invoice->reference }}<span>{{ $invoice->patient_category === 1 ? 'CNS' : '' }}</span>
								</td>
								<td scope="col">{{ $invoice->date }}</td>
								<td scope="col" class="text-end">{{ $invoice->session }}</td>
								<td scope="col">{{ $invoice->name }}</td>
								<td scope="col">{{ $invoice->patient }}</td>
								<td scope="col" class="text-end">{{ $invoice->active ? $invoice->total . ' €' : '' }}</td>
								<td scope="col" class="invoice-item-print">
									<a class="float-end" href="{{ route('invoice.print', ['invoice' => $invoice->id]) }}" target="_blank" title=" {{ __('Print') }} "><i class="fas fa-print pe-none"></i></a>
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
