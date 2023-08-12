@extends('layouts.app', ['page_title' => '<i class="fas fa-user-tie me-2"></i>' . __('Admin')])

@php
	$default_country_code = config('project.default_country_code');
@endphp


@section('content')
	<style>
		#log-result {
			height: 500px;
		}
	</style>
	<div class="container">
		<div class="form-group row mt-4">
			<div class="col-12 d-flex">
				<div class="flex-grow-1">
					<button id="monitoring" class="btn btn-sm btn-primary me-3">Monitoring result</button>
				</div>
				<div class="input-group input-group-sm w-auto">
					<span class="input-group-text">OVH</span>
					<input type="number" min="100" class="form-control" placeholder="SMS credits >= 100">
					<button type="button" id="buy-sms-credits" class="btn btn-outline-secondary" style="border-color: #ced4da;">Buy</button>
				</div>
				{{-- <button type="button" id="buy-sms-credits" class="btn btn-sm btn-warning my-3 ms-3 float-end">OVH - Buy SMS credits</button> --}}
			</div>
		</div>
		<pre id="monitoring-result"></pre>
		<div class="form-group row">
			<div class="col-12">
				<div class="mt-3 mb-1">logs</div>
				<button type="button" id="laravel-log" class="btn btn-sm btn-primary btn-log mb-2 me-3">Laravel</button>
				<button type="button" id="reminder-log" class="btn btn-sm btn-primary btn-log mb-2 me-3">Reminder</button>
				<button type="button" id="agenda-log" class="btn btn-sm btn-primary btn-log mb-2 me-3">Agenda</button>
				<button type="button" id="monitoring-log" class="btn btn-sm btn-primary btn-log mb-2 me-3">Monitoring</button>
				<button type="button" id="log-truncate" class="btn btn-sm btn-outline-danger btn-log mb-2" disabled><i class="far fa-trash-can pe-none me-0"></i></button>
				<pre id="log-result"></pre>
			</div>
		</div>
	</div>
@endsection

@push('assets')
	@vite($entries)
@endpush
