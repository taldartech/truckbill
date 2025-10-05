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
                'Date' => $invoice->generated_date ? Carbon::parse($invoice->generated_date)->format('d-m-Y') : 'N/A',
                'Invoice No' => $invoice->manual_invoice_number ?? 'N/A',
                'Amount' => number_format($invoice->net_amount ?? 0, 2),
                'State' => $invoice->consignee->state->name ?? 'N/A',
                'Reverse Charge' => $invoice->reverse_charge ?? 'N/A',
                'Tax %' => ($invoice->applicable_tax_percentage ?? '0') . '%',
                'Invoice Type' => $invoice->invoice_type ?? 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'Date',
            'Invoice No',
            'Amount',
            'State',
            'Reverse Charge',
            'Tax %',
            'Invoice Type',
        ];
    }
}
