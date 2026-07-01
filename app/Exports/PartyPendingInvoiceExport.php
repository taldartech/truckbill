<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PartyPendingInvoiceExport implements FromView, ShouldAutoSize, WithColumnFormatting
{
    /**
     * @param  array{
     *     partyRows: Collection,
     *     grandTotalCollect: float,
     *     grandTotalPaid: float,
     *     grandTotalPending: float
     * }  $payload
     */
    public function __construct(
        private array $payload,
    ) {}

    public function view(): View
    {
        return view('reports.exports.party-pending-invoice', [
            'partyRows' => $this->payload['partyRows'],
            'grandTotalCollect' => $this->payload['grandTotalCollect'],
            'grandTotalPaid' => $this->payload['grandTotalPaid'],
            'grandTotalPending' => $this->payload['grandTotalPending'],
            'forExcel' => true,
        ]);
    }

    public function columnFormats(): array
    {
        $numFormat = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;

        return [
            'D' => $numFormat,
            'E' => $numFormat,
            'F' => $numFormat,
        ];
    }
}
