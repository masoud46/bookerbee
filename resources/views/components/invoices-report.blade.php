<h4 class="border-bottom pb-2">{{ __('Report') }}</h4>
<div class="row">
	<div class="col-sm-6 mb-3">
		<label class="col-12 col-form-label position-relative">
			{{ __('Start') }}
		</label>
		<input id="report-start" type="date" class="form-control" value="{{ $historyStart }}">
	</div>
	<div class="col-sm-6 mb-3">
		<label class="col-12 col-form-label position-relative">
			{{ __('End') }} <small class="text-muted fst-italic">{{ __('(included)') }}</small>
		</label>
		<input id="report-end" type="date" class="form-control" value="{{ $historyEnd }}">
	</div>
</div>
<button id="print-report" data-url="{{ $printUrl }}" class="btn btn-outline-secondary float-end">
	<i class="fas fa-print"></i>
	{{ __('Print') }}
</button>
<button id="export-report" data-url="{{ $exportUrl }}" class="btn btn-primary float-end me-3">
	<i class="fas fa-file-excel"></i>
	{{ __('Export to Excel') }}
</button>
