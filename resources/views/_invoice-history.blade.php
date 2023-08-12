@extends('layouts.app', ['page_title' => '<i class="fas fa-rectangle-list me-2"></i>' . __('History')])

@php
// dd($invoices->count());
@endphp

@section('content')
<div class="container">
	<div class="row">
		<div class="col-12 my-4">
			<a href="">Excel</a>
		</div>

		<div class="col-12 table-responsive">
			@include('invoice-history-table', [$invoices, $total])
		</div>

		<div class="col-12">
			Total: <strong>{{ $total }} €</strong>
		</div>
	</div>
</div>

@endsection