@php($excel = isset($total_float))
<h5 class="fw-bold text-center">{{ strtoupper(Auth::user()->lastname) }}, {{ Auth::user()->firstname }}</h5>
<h5 class="fw-bold text-center">{{ __('Report: :date1 - :date2', ['date1' => $start, 'date2' => $end]) }}</h5>
<h5 class="pb-3"></h5>
<table id="prescription" class="table table-sm xtable-striped">
	<thead>
		<tr>
			<th scope="col">{{ __('Ref.') }}</th>
			<th scope="col">{{ __('Date') }}</th>
			<th scope="col">{{ __('Patient') }}</th>
			<th scope="col">{{ __('Insured') }}</th>
			<th scope="col">{{ __('Amount') }}</th>
		</tr>
	</thead>
	<tbody class="bg-white">
		@foreach ($invoices as $invoice)
			<tr class="items-table-item user-select-none">
				<td scope="col" class="invoice-item-reference">
					{{ $invoice->reference }}
				</td>
				<td scope="col">{{ $invoice->date }}</td>
				<td scope="col">{{ $invoice->name }}</td>
				<td scope="col">{{ $invoice->patient }}</td>
				<td scope="col" class="text-end">{{ $excel ? $invoice->total_float : $invoice->total . ' €' }}</td>
			</tr>
		@endforeach
		<tr class="items-table-item user-select-none">
			<td scope="col" style="padding-top: 3mm; border-color: transparent;"></td>
			<td scope="col" style="padding-top: 3mm; border-color: transparent;"></td>
			<td scope="col" style="padding-top: 3mm; border-color: transparent;"></td>
			<td scope="col" class="text-end" style="padding-top: 3mm; border-color: transparent;">
				<h5><strong>{{ __('Total') }}</strong></h5>
			</td>
			<td scope="col" class="text-end" style="padding-top: 3mm; border-color: transparent;">
				<h5><strong>{{ $excel ? $total_float : $total . ' €' }}</strong></h5>
			</td>
		</tr>
	</tbody>
</table>
