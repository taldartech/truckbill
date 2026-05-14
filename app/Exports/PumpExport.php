<?php

namespace App\Exports;

use App\Models\Pump;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PumpExport implements FromCollection, ShouldAutoSize, WithEvents
{
    /** @var Collection<int, array<string, mixed>> */
    private Collection $pumpData;

    private string $startDate;
    private string $endDate;

    /** @var string|int */
    private $pumpId;

    private string $pumpName;

    public function __construct(Collection $pumpData, string $startDate, string $endDate, string|int $pumpId)
    {
        $this->pumpData = $pumpData;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->pumpId = $pumpId;
        $this->pumpName = Pump::find($pumpId)->pump_name ?? 'Pump Report';
    }

    public function collection()
    {
        $data = collect();

        $pump = Pump::find($this->pumpId);
        if (!$pump) {
            return $data;
        }

        $data->push(['Pump:', $pump->pump_name]);
        $data->push(['Date Range:', $this->startDate . ' to ' . $this->endDate]);
        $data->push([]);

        $data->push([
            'LR Number',
            'Date',
            'Truck Number',
            'Destination',
            'Amount ()',
            'Status',
        ]);

        $grandTotal = 0;

        foreach ($this->pumpData as $item) {
            $grandTotal += (float) ($item['amount'] ?? 0);

            $dateStr = $item['date'] instanceof Carbon
                ? $item['date']->format('d-m-Y')
                : (string) $item['date'];

            $status = ucfirst(str_replace('-', ' ', $item['payment_status'] ?? 'N/A'));

            $lrs = $item['lrs'] ?? collect();
            foreach ($lrs as $lr) {
                $destination = trim(($lr->destination_city ?? '') . ', ' . ($lr->destination_state ?? 'N/A'), ' ,');

                $data->push([
                    $lr->lr_number ?? 'N/A',
                    $dateStr,
                    $lr->truck->truck_number ?? 'N/A',
                    $destination,
                    $item['amount'],
                    $status,
                ]);
            }
        }

        $data->push(['', '', '', 'Total:', $grandTotal, '']);

        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastRow = $event->sheet->getHighestRow();
                $event->sheet->getStyle("D{$lastRow}:E{$lastRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'borders' => [
                        'top' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                $event->sheet->getStyle("E1:E{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');
            },
        ];
    }
}
