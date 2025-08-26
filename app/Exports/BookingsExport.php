<?php

namespace App\Exports;

use App\Models\FreightBooking;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BookingsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $query = FreightBooking::with([
            'company',
            'transporter',
            'sourceCity',
            'destinationCity',
            'truck',
            'truck.owner'
        ]);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay()
            ]);
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'LR No',
            'Date',
            'From',
            'To',
            'Transporter',
            'Vehicle No',
            'Amount (₹)',
            'Status',
            'Created At'
        ];
    }

    public function map($booking): array
    {
        return [
            $booking->lr_no,
            $booking->date ? $booking->date->format('d-M-Y') : 'N/A',
            optional($booking->sourceCity)->name,
            optional($booking->destinationCity)->name,
            optional($booking->transporter)->name,
            optional($booking->truck)->vehicle_number ?? 'N/A',
            number_format($booking->total_amount, 2),
            ucfirst(str_replace('_', ' ', $booking->status)),
            $booking->created_at->format('d-M-Y H:i')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9EAD3']
                ]
            ],
            // Set border for all cells
            'A1:I' . ($this->collection()->count() + 1) => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }
}
