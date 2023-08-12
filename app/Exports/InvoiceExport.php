<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InvoiceExport implements
	// ShouldAutoSize,
	WithTitle,
	WithHeadings,
	WithHeadingRow,
	WithStyles,
	WithColumnFormatting,
	WithMapping,
	FromCollection {

	use Exportable;

	protected $from;
	protected $to;
	protected $invoices;
	protected $total;

	public function __construct($from, $to, $invoices, $total) {
		$this->from = $from;
		$this->to = $to;
		$this->invoices = $invoices;
		$this->total = $total;
	}

	public function title(): string {
		return __("From :start_date to :end_date", [
			'start_date' => $this->from,
			'end_date' => $this->to
		]);
	}

	public function headings(): array {
		return [
			__('Ref.'),
			__('Date'),
			__('Patient'),
			__('Insured'),
			__('Amount'),
		];
	}

	public function headingRow(): int {
		return 2;
	}

	public function map($invoice): array {
		return [
			$invoice->reference,
			$invoice->date,
			$invoice->name,
			$invoice->patient,
			$invoice->total_float,
		];
	}

	public function collection() {
		return collect([
			(object) [
				'reference' => '',
				'date' => '',
				'name' => '',
				'patient' => __('Total'),
				'total_float' => $this->total,
			],
			...$this->invoices,
			(object) [
				'reference' => '',
				'date' => '',
				'name' => '',
				'patient' => __('Total'),
				'total_float' => $this->total,
			],
		]);
	}

	public function columnFormats(): array {
		return [
			// 'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
			'E' => NumberFormat::FORMAT_CURRENCY_EUR,
		];
	}

	public function styles(Worksheet $sheet) {
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
				'font' => ['bold' => true],
				'fill' => [
					'fillType' => Fill::FILL_SOLID,
					'startColor' => ['argb' => 'E9E9E9'],
				]
			],

			// Styling a specific cell by coordinate.
			// 'B2' => ['font' => ['italic' => true]],

			// Styling an entire column.
			// 'C'  => ['font' => ['size' => 16]],
		];
	}
}
