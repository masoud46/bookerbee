@php($excel = isset($total_float))
<h5 class="fw-bold text-center">{{ strtoupper(Auth::user()->lastname) }}, {{ Auth::user()->firstname }}</h5>
<h5 class="fw-bold text-center">{{ $start }} - {{ $end }}</h5>
<h5 class="pb-3"></h5>
<table class="table table-sm">
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
			@php($style = $invoice->active ? '' : 'color:#cc0000;background-color:#fff4f4;')
			<tr class="items-table-item user-select-none">
				<td scope="col" style="{{ $style }}" class="invoice-item-reference">
					{{ $invoice->reference }}
				</td>
				<td scope="col" style="{{ $style }}">{{ $invoice->date }}</td>
				<td scope="col" style="{{ $style }}">{{ $invoice->name }}</td>
				<td scope="col" style="{{ $style }}">{{ $invoice->patient }}</td>
				@php($style .= 'text-align:right;')
				@if ($invoice->active)
					<td scope="col" style="{{ $style }}" class="text-end">{{ $excel ? $invoice->total_float : $invoice->total . ' €' }}</td>
				@else
					<td scope="col" style="{{ $style }}">-</td>
				@endif
			</tr>
		@endforeach
		<tr class="items-table-item user-select-none">
			<td scope="col" style="padding-top: 3mm; border-color: transparent;"></td>
			<td scope="col" style="padding-top: 3mm; border-color: transparent;"></td>
			<td scope="col" style="padding-top: 3mm; border-color: transparent;"></td>
			<td scope="col" style="text-align: right; padding-top: 3mm; border-color: transparent;">
				<h5><strong>{{ __('Total') }}</strong></h5>
			</td>
			<td scope="col" style="text-align: right; padding-top: 3mm; border-color: transparent;">
				<h5><strong>{{ $excel ? $total_float : $total . ' €' }}</strong></h5>
			</td>
		</tr>
	</tbody>
</table>
