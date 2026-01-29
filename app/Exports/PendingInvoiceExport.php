<?php

namespace App\Exports;

use App\Models\LorryReceipt;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PendingInvoiceExport implements FromCollection, WithHeadings, WithColumnFormatting
{
    protected $customerId;
    protected $startDate;
    protected $endDate;

    public function __construct($customerId = null, $startDate = null, $endDate = null)
    {
        $this->customerId = $customerId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $query = LorryReceipt::with([
            'truck:id,truck_number',
            'customer:id,name'
        ])
            ->where('company_id', Auth::user()->company_id)
            ->whereNotNull('memo_id')
            ->where('invoice_generated', 0)
            ->where('delivery_status', 'Delivered');

        // optionally filter by customer
        if ($this->customerId) {
            $query = $query->where('customer_id', $this->customerId);
        }

        // optionally filter by date range
        if ($this->startDate && $this->endDate) {
            $query = $query->whereBetween('gr_date', [$this->startDate, $this->endDate]);
        }

        return $query->orderBy('gr_date', 'desc')->get()->map(function ($lr) {
            $amount = $lr->actual_amount ?? 0;
            return [
                'Truck No' => $lr->truck->truck_number ?? 'N/A',
                'Customer' => $lr->customer->name ?? 'N/A',
                'From Place' => $lr->source_city,
                'To Place' => $lr->destination_city,
                'Consignment No' => $lr->lr_number,
                'Date' => Date::dateTimeToExcel(\Carbon\Carbon::parse($lr->gr_date)),
                'Lane' => $lr->lane,
                'QTY(MT)' => number_format($lr->weight, 3),
                'Rate' => number_format($lr->fr_rate, 2),
                'Damage' => number_format($lr->damage_amount, 2),
                'Shortage' => number_format($lr->shortage_amount, 2),
                'Amt.' => number_format($amount, 2),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Truck No',
            'Customer',
            'From Place',
            'To Place',
            'Consignment No',
            'Date',
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
            'F' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
