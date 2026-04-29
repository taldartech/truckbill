<?php

namespace App\Exports;

use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Invoice;
use App\Models\Truck;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DispatchExport implements FromView, ShouldAutoSize, WithColumnFormatting
{
    private $startDate, $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): \Illuminate\Contracts\View\View
    {
        $user = Auth::user()->company_id;
        $invoiceByLrId = collect();
        if ($this->startDate || $this->endDate) {
            $query = LorryReceipt::with([
                'freightBookings' => function ($q) {
                    $q->with(['voucherPayments']);
                },
                'consignor',
                'consignee',
                'customer',
                'truck'
            ])->whereBetween('gr_date', [$this->startDate, $this->endDate])
                ->orderBy('gr_date', 'asc');

            if ($user) {
                $query->where('company_id', $user);
            }

            $lrs = $query->get();
            $lrIds = $lrs->pluck('id')->all();
            if (! empty($lrIds)) {
                $invoices = Invoice::query()
                    ->where('company_id', $user)
                    ->where(function ($invoiceQuery) use ($lrIds) {
                        foreach ($lrIds as $lrId) {
                            $invoiceQuery->orWhereJsonContains('lr_ids', (int) $lrId);
                        }
                    })
                    ->get(['id', 'invoice_number', 'manual_invoice_number', 'lr_ids']);

                $invoiceByLrId = $invoices->flatMap(function ($invoice) {
                    return collect($invoice->lr_ids ?? [])->mapWithKeys(function ($lrId) use ($invoice) {
                        return [(int) $lrId => $invoice];
                    });
                });
            }
        } else {
            $lrs = [];
        }

        // When exporting, let the view know it's being rendered for Excel
        return view('reports.exports.dispatch', [
            'lrs'      => $lrs,
            'invoiceByLrId' => $invoiceByLrId,
            'forExcel' => true,
        ]);
    }

    /**
     * @return array
     */
    public function columnFormats(): array
    {
        $numFormat = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;
        $weightFormat = '#,##0.000';

        return [
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'I' => $weightFormat,
            'J' => $weightFormat,
            'L' => $numFormat,
            'M' => $numFormat,
            'T' => $numFormat,
            'U' => $numFormat,
            'V' => $numFormat,
            'W' => $numFormat,
            'X' => $numFormat,
            'Y' => $numFormat,
            'Z' => $numFormat,
            'AA' => $numFormat,
            'AB' => $numFormat,
            'AC' => $numFormat,
            'AD' => $numFormat,
            'AE' => $numFormat,
            'AF' => $numFormat,
            'AG' => $numFormat,
            'AH' => $numFormat,
            'AQ' => NumberFormat::FORMAT_NUMBER,
            'AR' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
