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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class InvoiceExportView implements
	WithTitle,
	WithColumnFormatting,
	WithStyles,
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

		$last_row = $sheet->getHighestRow();
		$last_col = $sheet->getHighestColumn();

		$row = $sheet->getStyle('A' . $last_row . ':' . $last_col . $last_row);
		$row->getFont()
			->setBold(true);
		$row->getFill()
			->setFillType(Fill::FILL_SOLID)
			->getStartColor()->setARGB('E9E9E9');

		$sheet->getStyle('D' . $last_row . ':D' . $last_row)
			->getAlignment()
			->setHorizontal(Alignment::HORIZONTAL_RIGHT);

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
}
