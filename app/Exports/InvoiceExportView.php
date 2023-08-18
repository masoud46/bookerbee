<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class InvoiceExportView implements
	WithTitle,
	WithColumnFormatting,
	WithStyles,
	// WithEvents,
	FromView {

	protected $start;
	protected $end;
	protected $invoices;
	protected $total_float;

	public function __construct($start, $end, $invoices, $total_float) {
		$this->start = $start;
		$this->end = $end;
		$this->invoices = $invoices;
		$this->total_float = $total_float;
	}

	public function title(): string {
		return __("From :date1 to :date2", [
			'date1' => str_replace("/", "-", $this->start),
			'date2' => str_replace("/", "-", $this->end),
		]);
	}

	public function view(): View {
		return view('invoice-report', [
			'start' => $this->start,
			'end' => $this->end,
			'invoices' => $this->invoices,
			'total_float' => $this->total_float,
		]);
	}

	public function columnFormats(): array {
		return [
			// 'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
			'E' => NumberFormat::FORMAT_CURRENCY_EUR,
		];
	}

	public function styles(Worksheet $sheet) {
		// $sheet->getRowDimension('1')->setRowHeight(20);
		// $sheet->getRowDimension('2')->setRowHeight(20);
		// $sheet->getRowDimension('3')->setRowHeight(20);

		$highest_row = $sheet->getHighestRow();
		$highest_column = $sheet->getHighestColumn();

		$row = $sheet->getStyle('A' . $highest_row . ':' . $highest_column . $highest_row);
		$row->getFont()
			->setBold(true);
		$row->getFill()
			->setFillType(Fill::FILL_SOLID)
			->getStartColor()->setARGB('E9E9E9');

		return [
			// Style the first.
			1 => [
				'font' => ['size' => 12],
				'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
			],

			2 => [
				'font' => ['size' => 12],
				'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
			],

			4 => [
				'font' => ['bold' => true],
				'fill' => [
					'fillType' => Fill::FILL_SOLID,
					'startColor' => ['argb' => 'E9E9E9'],
				]
			],
		];
	}

	// public function registerEvents(): array {
	// 	return [
	// 		AfterSheet::class => function (AfterSheet $event) {
	// 			// $event->sheet returns \Maatwebsite\Excel\Sheet which has all the methods of \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
	// 			$highest_row = $event->sheet->getHighestRow();
	// 			$last_data_row = $highest_row - 1;

	// 			$event->sheet->setCellValue('E' . $highest_row, "=SUM(E5:E{$last_data_row})");
	// 		},
	// 	];
	// }
}
