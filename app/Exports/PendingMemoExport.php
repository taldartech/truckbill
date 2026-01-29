<?php

namespace App\Exports;

use App\Models\LorryReceipt;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PendingMemoExport implements FromCollection, WithHeadings, WithColumnFormatting
{
    protected $customerId;
    protected $startDate;
    protected $endDate;
    protected $status;

    public function __construct($customerId = null, $startDate = null, $endDate = null, $status = 'delivered')
    {
        $this->customerId = $customerId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->status = $status;
    }

    public function collection()
    {
        $query = LorryReceipt::with([
            'truck:id,truck_number',
            'customer:id,name'
        ])
            ->where('company_id', Auth::user()->company_id)
            ->whereNull('memo_id');

        // optionally filter by customer
        if ($this->customerId) {
            $query = $query->where('customer_id', $this->customerId);
        }

        // optionally filter by date range
        if ($this->startDate && $this->endDate) {
            $query = $query->whereBetween('gr_date', [$this->startDate, $this->endDate]);
        }

        // optionally filter by LR status
        $normalizedStatus = strtolower($this->status ?? 'delivered');
        if ($normalizedStatus === 'in-progress') {
            $query = $query->where('delivery_status', 'In-Progress');
        } elseif ($normalizedStatus === 'delivered') {
            $query = $query->where('delivery_status', 'Delivered');
        } elseif ($normalizedStatus === 'both') {
            $query = $query->whereIn('delivery_status', ['In-Progress', 'Delivered']);
        }

        return $query->orderBy('gr_date', 'desc')->get()->map(function ($lr) {
            $amount = $lr->actual_amount ?? 0;
            return [
                'Date' => Date::dateTimeToExcel(\Carbon\Carbon::parse($lr->gr_date)),
                'Consignment No' => $lr->lr_number,
                'Truck No' => $lr->truck->truck_number ?? 'N/A',
                'Customer' => $lr->customer->name ?? 'N/A',
                'From Place' => $lr->source_city,
                'To Place' => $lr->destination_city,
                'Lane' => $lr->lane,
                'QTY(MT)' => (float) ($lr->weight ?? 0),
                'Rate' => (float) ($lr->fr_rate ?? 0),
                'Damage' => (float) ($lr->damage_amount ?? 0),
                'Shortage' => (float) ($lr->shortage_amount ?? 0),
                'Amt.' => (float) $amount,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Date',
            'Consignment No',
            'Truck No',
            'Customer',
            'From Place',
            'To Place',
            'Lane',
            'QTY(MT)',
            'Rate',
            'Damage',
            'Shortage',
            'Amt.',

        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'H' => '#,##0.000',
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
