@php
	// echo "<pre>";
	// print_r(Auth::user()->toArray());
	// print_r($appointments);
	// echo "</pre>";
	
	// if (!$invoice) {
	//     echo '<pre>No invoice found!</pre>';
	//     exit();
	// }
	
	$titles = json_decode($user->titles);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{{ config('app.name', 'Laravel') }} - Invoice</title>

	<!-- Scripts -->
	@vite('resources/scss/pages/print-invoice.scss')
</head>

<body>

	<table id="prescription">
		<tbody>
			<tr>
				<td></td>
				<td colSpan="9">
					<div class="header">
						<div class="header-left">
							<div class="user">
								<div class="user-name">
									{{-- <div>{{ $user->lastname }}, {{ $user->firstname }}</div> --}}
									<div>DOE, John</div>
									@foreach ($titles as $title)
										<div>{{ $title }}</div>
									@endforeach
									@if ($user->address_line1)
										<div>{{ $user->address_line1 }}</div>
									@endif
									@if ($user->address_line2)
										<div>{{ $user->address_line2 }}</div>
									@endif
									@if ($user->address_line3)
										<div>{{ $user->address_line3 }}</div>
									@endif
									<div>{{ $user->address_country }} - {{ $user->address_code }} {{ $user->address_city }}</div>
								</div>
								<div class="user-info">
									<div class="user-tel">
										<div>Tél :</div>
										<div>{{ $user->phone_prefix }} {{ $user->phone_number }}</div>
									</div>
									<div class="user-fax">
										<div>Fax :</div>
										<div>{{ $user->fax_prefix }} {{ $user->fax_number }}</div>
									</div>
									<div class="user-email">
										<div>Email :</div>
										<div>{{ $user->email }}</div>
									</div>
								</div>
							</div>
							<div class="patient">
								<div class="patient-info">
									<div class="patient-code">
										<div>Matricule :</div>
										<div>{{ $invoice->patient_code }}</div>
									</div>
									<div class="patient-name">
										<div>Patient :</div>
										<div>{{ $invoice->name }}</div>
									</div>
									<div class="patient-acc-num">
										<div>N° Accident :</div>
										<div>{{ $invoice->acc_number }}</div>
									</div>
									<div class="patient-acc-date">
										<div>Date Accident :</div>
										<div>{{ $invoice->acc_date ? date('d/m/Y', strtotime($invoice->acc_date)) : '' }}</div>
									</div>
								</div>
							</div>
							<div class="prescriber">
								<div>Prescripteur :</div>
								<div>{{ $invoice->doc_code }}</div>
							</div>
						</div>
						<div class="header-right">
							<div class="user-code">
								<div>Code psychothérapeute :</div>
								<div>{{ $user->code }}</div>
							</div>
							<div class="cns">
								{{-- &lt;réservé CNS&gt; --}}
								<button onClick="window.print()">Imprimer la mémoire d'honoraires</button>
							</div>
							<div class="header-right-address">
								<div>{{ $invoice->patient_lastname }}, {{ $invoice->patient_firstname }}</div>
								@if ($invoice->patient_address_line1)
									<div>{{ $invoice->patient_address_line1 }}</div>
								@endif
								@if ($invoice->patient_address_line2)
									<div>{{ $invoice->patient_address_line2 }}</div>
								@endif
								@if ($invoice->patient_address_line3)
									<div>{{ $invoice->patient_address_line3 }}</div>
								@endif
								<div>{{ $invoice->patient_address_country }} - {{ $invoice->patient_address_code }}
									{{ $invoice->patient_address_city }}</div>
							</div>
							<div class="header-right-date">
								<div>Date ordonnance :</div>
								<div>{{ $invoice->doc_date ? date('d/m/Y', strtotime($invoice->doc_date)) : '' }}</div>
							</div>
						</div>
					</div>
				</td>
			</tr>
			<tr class="title">
				<td></td>
				<td colSpan="9">
					<div>
						<div class="title-num">MÉMOIRE D’HONORAIRES &nbsp;&nbsp; N° : <span>{{ $invoice->reference }}</span></div>
						<div class="title-date">du : <span>{{ date('d/m/Y', strtotime($invoice->created_at)) }}</span></div>
					</div>
				</td>
			</tr>
			<tr class="table-row table-header">
				<td></td>
				<td>Exécutant</td>
				<td>Lieu</td>
				<td>Date</td>
				<td>Code Acte</td>
				<td colSpan="2">Libellé</td>
				<td>Montant</td>
				<td>Part.pers.*</td>
				<td></td>
			</tr>

			@php($i = 0)
			@foreach ($appointments as $app)
				@php($i++)
				<tr class="table-row">
					<td class="row-num">{{ sprintf('%02d', $i) }}</td>
					<td>{{ $user->code }}</td>
					<td>{{ $app->location_code }}</td>
					<td>{{ date('d/m/Y', strtotime($app->done_at)) }}</td>
					<td>{{ $app->type_code }}</td>
					<td colSpan="2">{{ $app->description }}</td>
					<td class="currency">{{ $app->amount }}</td>
					<td class="currency">{{ $app->insurance ?? '' }}</td>
					<td>€</td>
				</tr>
			@endforeach

			@for ($i = $appointments->count(); $i < 10; $i++)
				<tr class="table-row">
					<td class="row-num">{{ sprintf('%02d', $i + 1) }}</td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td colSpan="2"></td>
					<td class="currency"></td>
					<td class="currency"></td>
					<td>€</td>
				</tr>
			@endfor
			<tr class="total">
				<td></td>
				<td rowSpan="3" colSpan="5">
					<div class="total-left">
						<div class="total-sign">
							<div>Pour acquit, le :</div>
							<div>{{ $invoice->granted_at ? date('d/m/Y', strtotime($invoice->granted_at)) : '' }}</div>
						</div>
						<div>Signature et cachet du psychothérapeute</div>
					</div>
				</td>
				<td class="total-title">Total :</td>
				<td class="currency bold">{{ $invoice->total_amount ?? '' }}</td>
				<td class="currency bold">{{ $invoice->total_insurance ?? '' }}</td>
				<td>€</td>
			</tr>
			<tr class="total-amount">
				<td></td>
				<td class="total-title">Acompte à déduire :</td>
				<td colSpan="2" class="currency">{{ $invoice->prepayment ?? '' }}</td>
				<td>€</td>
			</tr>
			<tr class="total-amount">
				<td></td>
				<td class="total-title">A PAYER :</td>
				<td colSpan="2" class="currency bold">{{ $invoice->total_to_pay ?? '' }}</td>
				<td>€</td>
			</tr>
			<tr>
				<td></td>
				<td colSpan="9">
					<div class="body">
						<div class="text">
							<div>
								{{-- <div>{{ $user->bank_account }}</div>
								<div>{{ $user->bank_swift }}</div>
								<div>{{ sprintf("Lors du virement, veuillez indiquer votre nom et la référence %s", $invoice->reference) }}</div> --}}
								<div>LU00 0000 0000 0000 0000</div>
								<div>BGLLLULL</div>
								<div>{{ sprintf("Lors du virement, veuillez indiquer votre nom et la référence %s", "23/1") }}</div>
							</div>
						</div>
						<div class="elsewhere">
							<div>En cas de lieu différent du cabinet, veuillez préciser :</div>
							@if ($invoice->location_name)
								<div>{{ $invoice->location_name }}</div>
								<div>{{ $invoice->location_address }}</div>
								<div>{{ $invoice->location_country }} - {{ $invoice->location_code }} {{ $invoice->location_city }}</div>
							@endif
						</div>
						<div class="note">* en cas de prise en charge par l'assurance maladie-maternité</div>
						<div class="footer">La loi du 1er août 2018 relative à la protection des données à caractère personnel,
							respectivement le Règlement général sur la protection des données (RGPD) sont appliqués. Pour l'exercice de vos
							droits (informations, modifications, suppression...) vous pouvez directement contacter votre professionnel de
							santé.</div>
					</div>
				</td>
			</tr>
		</tbody>
	</table>

	<script>
		// window.onafterprint = function() {
		// 	window.onfocus = function() { // Firefox
		// 		window.close()
		// 	}
		// 	window.close() // Chrome
		// }

		// window.print()
	</script>

</body>

</html>
