<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exports filtered sales rows as Excel / CSV for the reports download action.
 */
class SalesExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private array $filters = []) {}

    /** Build the underlying sales query using the same filters as the report page. */
    public function query()
    {
        return app(ReportService::class)->salesQuery($this->filters);
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Reference', 'Date', 'Customer', 'Status', 'Subtotal', 'Tax', 'Discount', 'Total', 'Source'];
    }

    /**
     * Map each Sale row to the flat column list used by the spreadsheet.
     *
     * @return array<int, mixed>
     */
    public function map($sale): array
    {
        return [
            $sale->reference,
            $sale->created_at?->format('Y-m-d H:i'),
            $sale->customer?->full_name,
            $sale->status->label(),
            (float) $sale->subtotal,
            (float) $sale->tax,
            (float) $sale->discount,
            (float) $sale->total,
            ucfirst($sale->source),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
