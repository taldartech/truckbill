<?php

namespace App\Exports;

use App\Models\EwayBill;
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
        $companyId = Auth::user()->company_id;
        if (! $this->startDate || ! $this->endDate) {
            return collect([]);
        }

        $ewayBills = EwayBill::query()
            ->whereBetween('eway_bill_expiry', [$this->startDate, $this->endDate])
            ->whereHas('freightBooking', function ($query) use ($companyId) {
                $query->where('is_delivered', 0)
                    ->where('is_cancelled', 0);
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
            })
            ->with([
                'freightBooking.lr.truck',
                'freightBooking.lr.consignor',
                'freightBooking.lr.consignee',
                'freightBooking.lr.transporter',
                'freightBooking.transporter',
            ])
            ->orderBy('eway_bill_expiry', 'ASC')
            ->get();

        $data = [];
        $srNo = 1;
        foreach ($ewayBills as $ewayBill) {
            $freightBooking = $ewayBill->freightBooking;
            $lr = $freightBooking?->lr;
            $transporter = $freightBooking?->transporter ?? $lr?->transporter;

            $data[] = [
                'Sr No' => $srNo++,
                'LR No' => $lr?->lr_number,
                'GR Date' => $lr?->gr_date
                    ? Date::dateTimeToExcel(\Carbon\Carbon::parse($lr->gr_date))
                    : null,
                'Truck No' => $lr?->truck?->truck_number,
                'Weight' => (float) ($lr->weight ?? 0),
                'LR Rate' => (float) ($lr->fr_rate ?? 0),
                'LR Amount' => (float) ($lr->amount ?? 0),
                'Consignor' => $lr?->consignor?->name,
                'Consignee' => $lr?->consignee?->name,
                'Destination' => $lr?->destination_city,
                'Eway Bill No.' => $ewayBill->eway_bill_number ?? '-',
                'Eway Bill Expiry Date' => $ewayBill->eway_bill_expiry
                    ? Date::dateTimeToExcel(\Carbon\Carbon::parse($ewayBill->eway_bill_expiry))
                    : null,
                'Transporter/Truck Owner Name' => $transporter?->name,
                'Transporter/Truck Owner Mobile No.' => $transporter?->mobile_number,
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'LR No',
            'GR Date',

            'Truck No',
            'Weight',
            'LR Rate',
            'LR Amount',

            'Consignor',
            'Consignee',
            'Destination',
            'Eway Bill No.',
            'Eway Bill Expiry Date',
            'Transporter/Truck Owner Name',
            'Transporter/Truck Owner Mobile No.',
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
