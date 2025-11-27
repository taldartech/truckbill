<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class InvoiceExport implements FromCollection, WithHeadings, ShouldAutoSize, WithColumnFormatting
{
    private $startDate, $endDate;

    public function __construct($startDate, $endDate)
    {
        // Ensure dates are in the correct format
        $this->startDate = Carbon::parse($startDate)->startOfDay();
        $this->endDate = Carbon::parse($endDate)->endOfDay();
    }

    public function collection()
    {
        $companyId = Auth::user()->company_id;

        $query = Invoice::with(['consignee', 'consignee.state', 'company'])
            ->whereBetween('generated_date', [$this->startDate, $this->endDate]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $invoices = $query->orderBy('generated_date', 'desc')->get();

        return $invoices->map(function ($invoice, $index) {
            return [
                'Sr No' => $index + 1,
                'GSTIN/UIN of Recipient' => $invoice->consignee->gst_number ?? 'N/A',
                'Receiver' => $invoice->consignee->name ?? 'N/A',
                'Invoice No' => $invoice->manual_invoice_number ?? 'N/A',
                'Date' => Date::dateTimeToExcel(new Carbon($invoice->generated_date)),
                'Amount' => number_format($invoice->net_amount ?? 0, 2),
                'State' => $invoice->consignee->state->name ?? 'N/A',
                'Reverse Charge' => $invoice->company->gst_scheme == 'reverse charge' ? 'Yes' : 'No',
                'Applicable % of Tax' => "-",
                'Invoice Type' => "Regular B2B",
                'E-Commerce GSTIN' => "",
                'Rate' => "5.00",
                'Taxable Value' => number_format($invoice->net_amount ?? 0, 2),
                'Cess Amount' => "",
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'GSTIN/UIN of Recipient',
            'Receiver',
            'Invoice No',
            'Date',
            'Amount',
            'State',
            'Reverse Charge',
            'Applicable % of Tax',
            'Invoice Type',
            'E-Commerce GSTIN',
            'Rate',
            'Taxable Value',
            'Cess Amount',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => 'dd/mm/yyyy',
        ];
    }
}
