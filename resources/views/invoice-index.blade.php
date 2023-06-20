@extends('layouts.app', ['page_title' => '<i class="fas fa-rectangle-list me-2"></i>' . __('Statements')])

@php
	// function calculateTimeDelta()
	// {
	//	  $headers = [
	//			'X-Ovh-Application' => config('project.sms.ovh.application_key'),
	//			'Content-Type' => 'application/json; charset=utf-8',
	//	  ];
	
	//	  $client = new \GuzzleHttp\Client([
	//			'timeout' => 30,
	//			'connect_timeout' => 5,
	//	  ]);
	
	//	  $request = new \GuzzleHttp\Psr7\Request('GET', 'https://eu.api.ovh.com/1.0/auth/time', $headers);
	//	  $response = $client->send($request, ['headers' => $headers]);
	
	//	  $serverTimestamp = (int) (string) $response->getBody();
	//	  $time_delta = $serverTimestamp - (int) \time();
	
	//	  return $time_delta;
	// }
	
	// $url = 'https://eu.api.ovh.com/1.0/sms/sms-ms1296984-1';
	// $url_hlr = $url . '/hlr';
	// $url_hlr_id = $url_hlr . '/:id';
	
	// $headers = [
	//	  'Content-Type' => 'application/json; charset=utf-8',
	//	  'X-Ovh-Application' => config('project.sms.ovh.application_key'),
	//	  'X-Ovh-Consumer' => config('project.sms.ovh.consumer_key'),
	// ];
	
	// $client = new \GuzzleHttp\Client([
	//	  'timeout' => 30,
	//	  'connect_timeout' => 5,
	// ]);
	
	// echo '<pre>';
	
	// // $method = 'GET';
	// // $url = str_replace(':id', '11698476', $url_hlr_id); // done
	// // // $url = str_replace(':id', '11698471', $url_hlr_id); // error
	// // $body = '';
	
	// // $now = time() + calculateTimeDelta();
	// // $toSign = config('project.sms.ovh.application_secret') . '+' . config('project.sms.ovh.consumer_key') . '+' . $method . '+' . $url . '+' . $body . '+' . $now;
	// // $signature = '$1$' . sha1($toSign);
	// // $headers['X-Ovh-Signature'] = $signature;
	// // $headers['X-Ovh-Timestamp'] = $now;
	
	// // $request = new \GuzzleHttp\Psr7\Request($method, $url, $headers);
	// // $res = $client->sendAsync($request, ['headers' => $headers])->wait();
	// // dd(json_decode($res->getBody(), true));
	
	// $method = 'POST';
	// $url = $url_hlr;
	// $to = '+32472877055';
	// $to = preg_replace('/\s+/', '', $to);
	// $content = [
	//	  'receivers' => [$to],
	// ];
	// $body = json_encode($content, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
	
	// $now = time() + calculateTimeDelta();
	// $toSign = config('project.sms.ovh.application_secret') . '+' . config('project.sms.ovh.consumer_key') . '+' . $method . '+' . $url . '+' . $body . '+' . $now;
	// $signature = '$1$' . sha1($toSign);
	// $headers['X-Ovh-Signature'] = $signature;
	// $headers['X-Ovh-Timestamp'] = $now;
	
	// $request = new \GuzzleHttp\Psr7\Request($method, $url, $headers, $body);
	// $res = $client->sendAsync($request, ['headers' => $headers])->wait();
	// $res_array = json_decode($res->getBody(), true, 512, JSON_THROW_ON_ERROR);
	// print_r($res_array);
	
	// $hlr_id = $res_array['ids'][0];
	// echo PHP_EOL . 'hlr_id: ' . $hlr_id . PHP_EOL;
	
	// $method = 'GET';
	// $url = str_replace(':id', $hlr_id, $url_hlr_id); // done
	// $body = '';
	
	// do {
	//	  $now = time() + calculateTimeDelta();
	//	  $toSign = config('project.sms.ovh.application_secret') . '+' . config('project.sms.ovh.consumer_key') . '+' . $method . '+' . $url . '+' . $body . '+' . $now;
	//	  $signature = '$1$' . sha1($toSign);
	//	  $headers['X-Ovh-Signature'] = $signature;
	//	  $headers['X-Ovh-Timestamp'] = $now;
	
	//	  $request = new \GuzzleHttp\Psr7\Request($method, $url, $headers);
	//	  $res = $client->sendAsync($request, ['headers' => $headers])->wait();
	//	  $res_array = json_decode($res->getBody(), true, 512, JSON_THROW_ON_ERROR);
	
	//	  $status = $res_array['status'];
	// } while ($status === 'todo');
	
	// print_r($res_array);
	// echo '</pre>';
	// dd('done');
	
	// echo '<pre>';
	
	// $sms = new \App\Notifications\SmsMessage(['provider' => 'smsto']);
	// $sms_to = $sms
	//	  ->to(preg_replace('/\s+/', '', '+32 472 87 70 55'))
	//	  ->line('Hello World!')
	//	  ->line(':o)')
	//	  ->dryRun()
	//	  ->send();
	// print_r($sms_to);
	
	// $sms = new \App\Notifications\SmsMessage();
	// $ovh = $sms
	//	  ->to(preg_replace('/\s+/', '', '+32 0472 87 70 55'))
	//	  ->line('Hello World!')
	//	  ->line(':o)')
	//	  ->dryRun()
	//	  ->send();
	// print_r($ovh);

	// /* SendGrid */
	// $url = 'https://api.brevo.com/v3/smtp/email';
	// $api_key = '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';
	
	// $postData = [
	// 	'sender' => ['name' => 'BookerBee', 'email' => 'no-reply@bookerbee.com'],
	// 	'to' => [['name' => 'John DOE', 'email' => 'masoudf46@gmail.com']],
	// 	'subject' => 'Test email',
	// 	'htmlContent' => '<html><head></head><body><img src={{ params.LOGO_IMAGE_URL}} alt="BookerBee" height="35"><br><h1>Héllo this îs a test email from œuf</h1></body></html>',
	// 	'headers' => [
	// 		// 'X-Mailin-custom' => 'custom_header_1:custom_value_1|custom_header_2:custom_value_2|custom_header_3:custom_value_3',
	// 		'charset' => 'iso-8859-1',
	// 	],
	// 	'params' => [
	// 		'LOGO_IMAGE_URL' => 'https://bookerbee.com/build/images/logo.png',
	// 	],
	// ];
	
	// // $payload = json_encode($postData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
	// $payload = json_encode($postData);
	
	// $ch = curl_init($url);
	// $headers = [
	// 	'accept: application/json',
	// 	'content-type: application/json',
	// 	'api-key: ' . $api_key,
	// ];
	
	// curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	// $response = curl_exec($ch);
	// curl_close($ch);
	
	// if ($response === false) {
	// 	 print 'Could not get a response';
	// } else {
	// 	 var_dump($response);
	// }

	// /* Brevo */
	// $url = 'https://api.sendgrid.com/v3/mail/send';
	// $api_key = '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';
	// $path = __DIR__ . '/../../../public/build/images/logo.png';
	// $data = file_get_contents($path);
	// $logo = base64_encode($data);
	
	// $postData = [
	//     'personalizations' => [
	//         [
	//             'to' => [
	//                 [
	//                     'email' => 'masoudf46@gmail.com',
	//                     'name' => 'John DOE',
	//                 ],
	//             ],
	//         ],
	//     ],
	//     'from' => [
	//         'email' => 'no-reply@bookerbee.com',
	//         'name' => 'BookerBee',
	//     ],
	//     'subject' => 'Sénding with SendGrid îs œuf',
	//     'content' => [
	//         [
	//             'type' => 'text/plain',
	//             'value' => '& Sénding with SendGrid îs œuf',
	//         ],
	//         [
	//             'type' => 'text/html',
	//             // 'value' => '<html><head></head><body><h1><img src="https://bookerbee.com/build/images/logo.png" width="120" alt="BookerBee"><br>& Sénding with SendGrid îs œuf</h1></body></html>',
	//             'value' => '<html><head></head><body><h1><img src="cid:header_logo" height="35" alt="BookerBee"><br>& Sénding with SendGrid îs œuf</h1></body></html>',
	//         ],
	//     ],
	//     'attachments' => [
	//         [
	//             'type' => 'image/png',
	//             'filename' => 'logo.png',
	//             'disposition' => 'inline',
	//             'content_id' => 'header_logo',
	//             'content' => $logo,
	//         ],
	//     ],
	// ];
	
	// // $payload = json_encode($postData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
	// $payload = json_encode($postData);
	
	// $ch = curl_init($url);
	// $headers = [
	//     'Content-Type: application/json',
	//     'Authorization: Bearer ' . $api_key,
	// ];
	
	// curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	// $response = false;
	// // $response = curl_exec($ch);
	// // curl_close($ch);
	
	// if ($response === false) {
	//     print 'Could not get a response';
	// } else {
	//     var_dump($response);
	// }
	
	// echo '</pre>';
	
	$default_country_code = config('project.default_country_code');
	$limits = config('project.load_invoice_limits');
	$default_limit = $limits[0];
	$limit = intval(request()->route('limit') ?? $default_limit);
	$menu_items = [];
	
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
	    'title' => __('All the invoices'),
	    'href' => route('invoice.index', ['limit' => 'all']),
	];
@endphp

@section('content')
	<input type="hidden" id="invoice-show-url" value="{{ route('invoice.show', ['invoice' => '?id']) }}">
	<input type="hidden" id="invoice-print-url" value="{{ route('invoice.print', ['invoice' => '?id']) }}">
	@if (config('app.env') === 'local')
		<div class="d-flex justify-content-end mt-2">
			{{-- <a class="nav-link me-3" href="{{ route('email.change-email') }}">ChangeEmail</a>
			<a class="nav-link me-3" href="{{ route('email.change-password') }}">ChangePassword</a> --}}
			<a class="nav-link me-3" href="{{ route('email.reminder') }}" onclick="event.preventDefault(); document.getElementById('reminder-form').submit();">Remind</a>
			<form id="reminder-form" action="{{ route('email.reminder') }}" method="post" class="d-none">
				@method('put')
				@csrf
			</form>
			<a class="nav-link me-3" href="{{ route('email.appointment') }}" onclick="event.preventDefault(); document.getElementById('add-form').submit();">Add</a>
			<form id="add-form" action="{{ route('email.appointment') }}" method="post" class="d-none">
				@method('put')
				@csrf
				<input type="hidden" name="action" value="add">
			</form>
			<a class="nav-link me-3" href="{{ route('email.appointment') }}" onclick="event.preventDefault(); document.getElementById('update-form').submit();">Update</a>
			<form id="update-form" action="{{ route('email.appointment') }}" method="post" class="d-none">
				@method('put')
				@csrf
				<input type="hidden" name="action" value="update">
			</form>
			<a class="nav-link me-3" href="{{ route('email.appointment') }}" onclick="event.preventDefault(); document.getElementById('delete-form').submit();">Delete</a>
			<form id="delete-form" action="{{ route('email.appointment') }}" method="post" class="d-none">
				@method('put')
				@csrf
				<input type="hidden" name="action" value="delete">
			</form>
		</div>
	@endif
	<div class="container">
		<div class="row">
			<div id="patient-picker" class="col-md-6 my-4">
				<h4 class="border-bottom pb-2">{{ __('New statement') }}</h4>
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
							<li><a href="{{ $value['href'] }}" type="button" class="dropdown-item {{ $key === $limit ? 'text-bg-secondary opacity-75 disabled' : '' }}">{{ $value['title'] }}</a></li>
						@endforeach
					</ul>
				</span>
				<small class="h-100 d-flex align-items-center text-nowrap float-end"><span class="items-table-count me-1">{{ $invoices->count() }}</span>/<span class="items-table-total ms-1">{{ $invoices->count() }}</span></small>
			</div>
			<div class="col-md-6 col-lg-5 col-xl-4 mt-3 mt-sm-2 d-flex search-filter position-relative">
				<input class="items-table-filter-input form-control" placeholder="{{ __('Search') }}" value="">
				<div class="btn-search-filter position-absolute"><i class="fas fa-magnifying-glass search-filter-inactive"></i><i class="fas fa-times search-filter-active"></i></div>
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
