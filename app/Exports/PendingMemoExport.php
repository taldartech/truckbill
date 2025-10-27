<?php

namespace App\Exports;

use App\Models\LorryReceipt;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PendingMemoExport implements FromCollection, WithHeadings
{
    protected $customerId;

    public function __construct($customerId = null)
    {
        $this->customerId = $customerId;
    }

    public function collection()
    {
        $query = LorryReceipt::with([
            'truck:id,truck_number',
            'customer:id,name'
        ])
        ->where('company_id', Auth::user()->company_id)
        ->whereNull('memo_id')
        ->where('delivery_status', 'Delivered');
        
        // optionally filter by customer
        if ($this->customerId) {
            $query = $query->where('customer_id', $this->customerId);
        }

        return $query->orderBy('gr_date', 'desc')->get()->map(function($lr) {
            $amount = $lr->weight * $lr->fr_rate;
            return [
                'Truck No' => $lr->truck->truck_number ?? 'N/A',
                'From Place' => $lr->source_city,
                'To Place' => $lr->destination_city,
                'Consignment No' => $lr->lr_number,
                'Date' => date('d-m-Y', strtotime($lr->gr_date)),
                'Lane' => $lr->lane,
                'QTY(MT)' => number_format($lr->weight, 2),
                'Rate' => number_format($lr->fr_rate, 2),
                'Damage' => number_format($lr->damage_amount, 2),
                'Shortage' => number_format($lr->shortage_amount, 2),
                'Amt.' => number_format($amount, 2),
                'S.Tax' => '0.00',
                'S.B.Cess' => '0.00',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Truck No',
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
            'S.Tax',
            'S.B.Cess'
        ];
    }
}