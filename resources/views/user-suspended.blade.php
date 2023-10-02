@extends('layouts.app', ['page_title' => '<i class="fas fa-user me-2"></i>' . __('Account')])

@section('content')
	<div class="container">
		<div class="row">
			<div class="col mt-4">
				<h4 class="text-danger fw-bold">{{ __('Your account is suspended.') }} <i class="far fa-face-frown"></i></h4>
				{{ __('Please contact the administrator for more details.') }}
			</div>
		</div>
	</div>
@endsection
