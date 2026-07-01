<?php

namespace App\Exports;

use App\Models\Consignee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PartyLedgerExport implements FromView, ShouldAutoSize, WithColumnFormatting
{
    /**
     * @param  array{
     *     startDate: string|null,
     *     endDate: string,
     *     customerId: string|null,
     *     presetCustomer: Consignee|null,
     *     customer: Consignee|null,
     *     lrs: Collection,
     *     ledgerRows: Collection
     * }  $payload
     */
    public function __construct(
        private array $payload,
    ) {}

    public function view(): View
    {
        return view('reports.exports.party-ledger', [
            'customer' => $this->payload['customer'],
            'ledgerRows' => $this->payload['ledgerRows'],
            'forExcel' => true,
        ]);
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
