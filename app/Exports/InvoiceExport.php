<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class InvoiceExport implements FromCollection, WithHeadings, ShouldAutoSize
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
        
        $query = Invoice::with(['consignee', 'consignee.state'])
            ->whereBetween('generated_date', [$this->startDate, $this->endDate]);
            
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $invoices = $query->orderBy('generated_date', 'desc')->get();

        return $invoices->map(function ($invoice, $index) {
            return [
                'Sr No' => $index + 1,
                'Invoice No' => $invoice->manual_invoice_number ?? 'N/A',
                'Date' => $invoice->generated_date ? Carbon::parse($invoice->generated_date)->format('d-m-Y') : 'N/A',
                'Amount' => number_format($invoice->net_amount ?? 0, 2),
                'State' => $invoice->consignee->state->name ?? 'N/A',
                'Reverse Charge' => $invoice->invoice_type == 'reverse_charge' ? 'Yes' : 'No',
                'Applicable % of Tax' => "-",
                'Invoice Type' => "Regular B2B",
                'Rate' => "5.00",
                'Taxable Value' => number_format($invoice->net_amount ?? 0, 2),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'Invoice No',
            'Date',
            'Amount',
            'State',
            'Reverse Charge',
            'Applicable % of Tax',
            'Invoice Type',
            'Rate',
            'Taxable Value',
        ];
    }
}
