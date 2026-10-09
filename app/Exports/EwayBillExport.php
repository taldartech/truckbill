<?php

namespace App\Exports;

use App\Models\EwayBill;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\Truck;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EwayBillExport implements FromCollection, WithHeadings, ShouldAutoSize, WithColumnFormatting
{
    private $startDate, $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $user = Auth::user()->company_id;
        if ($this->startDate || $this->endDate) {
            $ewayBills = EwayBill::with([
                'freightBooking.lr.truck',
                'freightBooking.lr.consignor',
                'freightBooking.lr.consignee',
                'freightBooking.lr.transporter',
                'freightBooking.transporter',
            ])
            ->whereHas('freightBooking', function ($q) use ($user) {
                if ($user) {
                    $q->where('company_id', $user);
                }
                $q->where('is_delivered', 0)
                  ->where('is_cancelled', 0);
            })
            ->whereBetween('eway_bill_expiry', [$this->startDate, $this->endDate])
            ->orderBy('eway_bill_expiry', 'ASC')
            ->get();

            $data = [];
            $srNo = 1;
            foreach ($ewayBills as $ewayBill) {
                $fr = $ewayBill->freightBooking;
                if (!$fr || !$fr->lr) {
                    continue;
                }
                $transporter = $fr->transporter ?? $fr->lr->transporter;
                $data[] = [
                    "Sr No" => $srNo++,
                    "LR No" => $fr->lr->lr_number,
                    "GR Date" => $fr->lr->gr_date ? Date::dateTimeToExcel(\Carbon\Carbon::parse($fr->lr->gr_date)) : '',
                    "Truck No" => $fr->lr->truck?->truck_number ?? 'N/A',
                    "Weight" => (float) ($fr->lr->weight ?? 0),
                    "LR Rate" => (float) ($fr->lr->fr_rate ?? 0),
                    "LR Amount" => (float) ($fr->lr->amount ?? 0),
                    "Consignor" => $fr->lr->consignor?->name ?? 'N/A',
                    "Consignee" => $fr->lr->consignee?->name ?? 'N/A',
                    "Destination" => $fr->lr->destination_city ?? 'N/A',
                    "Eway Bill No." => $ewayBill->eway_bill_number ?? '-',
                    "Eway Bill Expiry Date" => $ewayBill->eway_bill_expiry ? Date::dateTimeToExcel(\Carbon\Carbon::parse($ewayBill->eway_bill_expiry)) : '',
                    "Transporter/Truck Owner Name" => $transporter?->name ?? 'N/A',
                    "Transporter/Truck Owner Mobile No." => $transporter?->mobile_number ?? 'N/A',
                ];
            }
            return collect($data);
        } else {
            return collect([]);
        }
    }

    public function headings(): array
    {
        return [
            "Sr No",
            "LR No",
            "GR Date",

            "Truck No",
            "Weight",
            "LR Rate",
            "LR Amount",

            "Consignor",
            "Consignee",
            "Destination",
            "Eway Bill No.",
            "Eway Bill Expiry Date",
            "Transporter/Truck Owner Name",
            "Transporter/Truck Owner Mobile No.",
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'E' => '0.000', // Weight (3 decimals)
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'L' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
