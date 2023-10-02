@php
	$template = Auth::check() ? 'app' : 'auth';
	$icon = $icon ?? 'far fa-lightbulb';
	$title = $title ?? __('Information');
@endphp

@extends('layouts.' . $template, ['page_title' => '<i class="' . $icon . ' me-2"></i>' . $title])

@section('content')
	<div class="container">
		<div class="row mt-5">
			<div class="col-12">
				<h5>{{ $message }}</h5>
			</div>
		</div>
	</div>
@endsection
