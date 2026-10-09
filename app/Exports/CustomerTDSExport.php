<?php

namespace App\Exports;

use App\Models\InvoicePayment;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CustomerTDSExport implements FromCollection, WithHeadings, ShouldAutoSize, WithColumnFormatting
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
        $company_id = Auth::user()->company_id;
        $query = InvoicePayment::query()
            ->with(['invoice.consignee', 'invoice.company'])
            ->where('tds_amount', '>', 0);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('payment_date', [$this->startDate, $this->endDate]);
        }

        if ($company_id) {
            $query->whereHas('invoice', function ($q) use ($company_id) {
                $q->where('company_id', $company_id);
            });
        }

        $payments = $query->orderBy('payment_date', 'asc')->get();

        return $payments->map(function ($p, $index) {
            $invoice = $p->invoice;
            $customer = $invoice?->consignee;
            $invNo = $invoice?->invoice_number ?: ($invoice?->manual_invoice_number ?: 'INV-'.$p->invoice_id);
            $totalInvoice = (float) ($invoice?->net_amount ?? $invoice?->total_amount ?? 0);
            $remitted = (float) $p->amount;
            $tds = (float) ($p->tds_amount ?? 0);
            $deduction = (float) ($p->deduction_amount ?? 0);
            $totalSettled = $remitted + $tds + $deduction;

            return [
                'Sr No' => $index + 1,
                'Payment Date' => $p->payment_date ? Date::dateTimeToExcel(\Carbon\Carbon::parse($p->payment_date)) : '',
                'Invoice No' => $invNo,
                'Customer Name' => $customer?->name ?? 'N/A',
                'GST Number' => $customer?->gst_number ?? 'N/A',
                'PAN Number' => $customer?->pan_number ?? 'N/A',
                'Invoice Amount' => $totalInvoice,
                'Remitted Amount' => $remitted,
                'TDS u/s 194C' => $tds,
                'Deductions' => $deduction,
                'Total Settled' => $totalSettled,
                'Payment Mode' => ucfirst($p->mode ?? ''),
                'Remark' => $p->remark ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'Payment Date',
            'Invoice No',
            'Customer Name',
            'GST Number',
            'PAN Number',
            'Invoice Amount',
            'Remitted Amount',
            'TDS u/s 194C',
            'Deductions',
            'Total Settled',
            'Payment Mode',
            'Remark',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
