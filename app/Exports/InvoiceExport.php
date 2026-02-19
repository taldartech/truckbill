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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

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

        $invoices = $query->orderBy('generated_date', 'asc')->get();

        return $invoices->map(function ($invoice, $index) {
            return [
                'Sr No' => $index + 1,
                'GSTIN/UIN of Recipient' => $invoice->consignee->gst_number ?? 'N/A',
                'Receiver' => $invoice->consignee->name ?? 'N/A',
                'Invoice No' => $invoice->manual_invoice_number ?? 'N/A',
                'Date' => Date::dateTimeToExcel(new Carbon($invoice->generated_date)),
                'Amount' => (float) ($invoice->net_amount ?? 0),
                'State' => $invoice->consignee->state->name ?? 'N/A',
                'Reverse Charge' => $invoice->company->gst_scheme == 'reverse charge' ? 'Yes' : 'No',
                'Applicable % of Tax' => "-",
                'Invoice Type' => "Regular B2B",
                'E-Commerce GSTIN' => "",
                'Rate' => "5.00",
                'Taxable Value' => (float) ($invoice->net_amount ?? 0),
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
            'E' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
