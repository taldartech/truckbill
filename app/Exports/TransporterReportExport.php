<?php

namespace App\Exports;

use App\Models\LorryReceipt;
use App\Models\PaymentMode;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TransporterReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithColumnFormatting
{
    private $startDate;
    private $endDate;
    private $transporterId;
    private $truckId;
    private $referenceBy;
    private $paymentNames;

    public function __construct($startDate, $endDate, $transporterId, $truckId, $referenceBy)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->transporterId = $transporterId;
        $this->truckId = $truckId;
        $this->referenceBy = $referenceBy;
        $this->paymentNames = PaymentMode::pluck('name')->toArray();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $company = Auth::user()->company;
        $query = LorryReceipt::whereBetween('gr_date', [$this->startDate, $this->endDate]);
        if ($company) {
            $query->where('company_id', $company->id);
        }
        if ($this->transporterId) {
            $query->where('transporter_id', $this->transporterId);
        }
        if ($this->truckId) {
            $query->where('truck_id', $this->truckId);
        }
        if ($this->referenceBy) {
            $query->where('reference_by', $this->referenceBy);
        }
        $query->orderBy('gr_date', 'asc');

        $lorryReceipts = $query->get();
        $data = [];

        foreach ($lorryReceipts as $lr) {
            $fr = $lr->freightBookings->last();
            $payments = array_fill_keys($this->paymentNames, 0);
            if ($fr) {
                $vouchers = \App\Models\VoucherPayment::where('freight_booking_id', $fr->id)->get();
                foreach ($vouchers as $voucher) {
                    $paymentMode = $voucher->payment_mode;
                    if (isset($payments[$paymentMode])) {
                        $payments[$paymentMode] += $voucher->amount;
                    }
                }
                $total_balance = $fr->balance_amount;
            } else {
                $total_balance = 0;
            }

            $row = [
                Date::dateTimeToExcel(\Carbon\Carbon::parse($lr->gr_date)),
                $lr->lr_number,
                $lr->gr_number,
                $lr->truck ? $lr->truck->truck_number : 'N/A',
                $lr->destination_city . ', ' . $lr->destination_state,
                (float) ($lr->weight ?? 0),
                (float) ($lr->unloading_weight ?? $lr->weight ?? 0),
                (float) ($lr->no_of_units ?? 0),
                $lr->consignor ? $lr->consignor->name : 'N/A',
                $lr->consignee ? $lr->consignee->name : 'N/A',
                (float) ($fr?->tr_fr_rate ?? 0),
                (float) ($fr?->tr_amount ?? 0),
                (float) ($fr?->tds_amount ?? 0),
            ];
            foreach ($this->paymentNames as $paymentName) {
                $row[] = (float) ($payments[$paymentName] ?? 0);
            }
            $row = array_merge($row, [
                (float) ($fr?->damage_amount ?? 0),
                (float) ($fr?->shortage_amount ?? 0),
                (float) ($fr?->misc_expense ?? 0),
                (float) ($total_balance ?? 0),
            ]);
            $data[] = $row;
        }
        return collect($data);
    }

    public function headings(): array
    {
        $headers = [
            'GR Date',
            'LR No',
            'GR No',
            'Truck No',
            'Destination',
            'Loading Weight',
            'Unloading Weight',
            'Bags',
            'Consignor Name',
            'Consignee Name',
            'TR Rate',
            'Tr Freight',
            'TDS',
        ];

        foreach ($this->paymentNames as $paymentName) {
            $headers[] = $paymentName;
        }

        $headers = array_merge($headers, [
            'Damage Amount',
            'Short Amount',
            'Misc Expenses',
            'Balance',
        ]);

        return $headers;
    }

    public function columnFormats(): array
    {
        $formats = [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'F' => '0.000', // Loading Weight (3 decimals)
            'G' => '0.000', // Unloading Weight (3 decimals)
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
        $paymentCount = count($this->paymentNames);
        $numFormat = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;
        for ($i = 0; $i < $paymentCount; $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(14 + $i);
            $formats[$col] = $numFormat;
        }
        for ($idx = 0; $idx < 4; $idx++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(14 + $paymentCount + $idx);
            $formats[$col] = $numFormat;
        }
        return $formats;
    }
}
