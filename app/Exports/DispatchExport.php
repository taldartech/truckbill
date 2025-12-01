<?php

namespace App\Exports;

use App\Models\LorryReceipt;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DispatchExport implements FromView, WithHeadings, ShouldAutoSize, WithColumnFormatting
{
    private $startDate, $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): \Illuminate\Contracts\View\View
    {
        $user = Auth::user()->company_id;
        if ($this->startDate || $this->endDate) {
            $query = LorryReceipt::with([
                'freightBookings' => function ($q) {
                    $q->with(['voucherPayments']);
                },
                'consignor',
                'consignee',
                'customer',
                'truck'
            ])->whereBetween('gr_date', [$this->startDate, $this->endDate])
                ->orderBy('gr_date', 'asc');

            if ($user) {
                $query->where('company_id', $user);
            }

            $lrs = $query->get();
        } else {
            $lrs = [];
        }
        return view('reports.exports.dispatch', compact('lrs'));
    }

    public function headings(): array
    {
        return [
            "Sr No",
            "GR Date",
            "LR No",
            "GR No",
            "Truck No",
            "Destination",
            "Invoice No.",
            "Weight",
            "Bags",
            "Fr rate",
            "Amount",
            "Unloading st.",
            "Distt.",
            "State",
            "Consignor",
            "Consignee",
            "GST Number",
            "Tr Rate",
            "Tr Freight",
            "TDS",
            "Cash Advance",
            "Bank",
            "Adjust In Advance",
            "Diesel",
            "Total",
            "Damage Amount",
            "Shortage Amount",
            "Detention",
            "Deviation",
            "Misc.",
            "Balance",
            "Diff",
            "Memo No.",
            "Bill No.",
            "Driver No.",
            "Transport Name",
            "Reference By",
            "Payment Made to",
            "TDS Deducted",
            "E-way Bill No.",
            "E-way Bill Expiry Date",
            "Remarks",
            "PAN No"
        ];
    }

    /**
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            // gr_date
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            // eway_bill_expiry_date
            'AO' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
