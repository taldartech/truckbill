<?php

namespace App\Exports;

use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Invoice;
use App\Models\Truck;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Shared\Date;

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
            $frs = FreightBooking::where('company_id', $user)
                ->whereBetween('eway_bill_expiry', [$this->startDate, $this->endDate])
                ->where('is_delivered', 0)
                ->where('is_cancelled', 0)
                ->orderBy('eway_bill_expiry', 'ASC')
                ->get();
            $data = [];
            $srNo = 1;
            foreach ($frs as $fr) {
                $data[] = [
                    "Sr No" => $srNo++,
                    "LR No" => $fr->lr->lr_number,
                    "GR Date" => Date::dateTimeToExcel(\Carbon\Carbon::parse($fr->lr->gr_date)),
                    "Truck No" => $fr->lr->truck->truck_number,
                    "Weight" => $fr->lr->weight,
                    "LR Rate" => $fr->lr->fr_rate,
                    "LR Amount" => $fr->lr->amount,
                    "Consignor" => $fr->lr->consignor->name,
                    "Consignee" => $fr->lr->consignee->name,
                    "Destination" => $fr->lr->destination_city,
                    "Eway Bill No." => $fr->eway_bill_number,
                    "Eway Bill Expiry Date" =>  Date::dateTimeToExcel(\Carbon\Carbon::parse($fr->eway_bill_expiry)),
                    "Transporter/Truck Owner Name" => $fr->transporter->name,
                    "Transporter/Truck Owner Mobile No." => $fr->transporter->mobile_number,
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
            'C' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY,
            'L' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
