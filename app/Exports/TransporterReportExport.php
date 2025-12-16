<?php

namespace App\Exports;

use App\Models\LorryReceipt;
use App\Models\PaymentMode;
use BcMath\Number;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Shared\Date;

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
            if ($fr->linked_lr_id) {
                $frs = \App\Models\FreightBooking::where('linked_lr_id', $fr->linked_lr_id)->get();
                foreach ($frs as $freight) {
                    $vouchers = \App\Models\VoucherLinked::where('freight_booking_id', $freight->id)->get();
                    foreach ($vouchers as $voucher) {
                        $paymentMode = $voucher->payment_mode;
                        if (isset($payments[$paymentMode])) {
                            $payments[$paymentMode] += $voucher->amount;
                        }
                    }
                }
                $total_balance = $frs->sum('balance_amount');
            } else {
                $vouchers = \App\Models\VoucherPayment::where('freight_booking_id', $fr->id)->get();
                foreach ($vouchers as $voucher) {
                    $paymentMode = $voucher->payment_mode;
                    if (isset($payments[$paymentMode])) {
                        $payments[$paymentMode] += $voucher->amount;
                    }
                }
                $total_balance = $fr->balance_amount;
            }

            $row = [
                Date::dateTimeToExcel(\Carbon\Carbon::parse($lr->gr_date)),
                $lr->lr_number,
                $lr->gr_number,
                $lr->truck ? $lr->truck->truck_number : 'N/A',
                $lr->destination_city . ', ' . $lr->destination_state,
                number_format($lr->weight ?? 0, 3),
                number_format($lr->no_of_units ?? 0, 2),
                $lr->consignor ? $lr->consignor->name : 'N/A',
                $lr->consignee ? $lr->consignee->name : 'N/A',
                number_format($fr->tr_fr_rate ?? 0, 2),
                number_format($fr->tr_amount ?? 0, 2),
                number_format($fr->tds_amount ?? 0, 2),
            ];
            foreach ($this->paymentNames as $paymentName) {
                $row[] = number_format($payments[$paymentName] ?? 0, 2);
            }
            $row = array_merge($row, [
                number_format($fr->damage_amount ?? 0, 2),
                number_format($fr->short_amount ?? 0, 2),
                number_format($fr->misc_expenses ?? 0, 2),
                number_format($total_balance ?? 0, 2),
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
            'Weight',
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
        return [
            'A' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
