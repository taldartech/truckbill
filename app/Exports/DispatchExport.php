<?php

namespace App\Exports;

use App\Models\LorryReceipt;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DispatchExport implements FromView, ShouldAutoSize, WithColumnFormatting
{
    private $startDate;

    private $endDate;

    private $balanceAsOnDate;

    private $customerId;

    private $transporterId;

    public function __construct($startDate, $endDate, $balanceAsOnDate = null, $customerId = null, $transporterId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->balanceAsOnDate = $balanceAsOnDate;
        $this->customerId = $customerId;
        $this->transporterId = $transporterId;
    }

    public function view(): View
    {
        $user = Auth::user()->company_id;
        if ($this->startDate || $this->endDate) {
            $query = LorryReceipt::with([
                'freightBookings' => function ($q) {
                    $q->with(['voucherPayments', 'customAmounts']);
                },
                'consignor',
                'consignee',
                'customer',
                'truck',
                'transporter',
            ])->whereBetween('gr_date', [$this->startDate, $this->endDate])
                ->orderBy('gr_date', 'asc');

            if ($user) {
                $query->where('company_id', $user);
            }

            if ($this->customerId) {
                $query->where('customer_id', $this->customerId);
            }

            if ($this->transporterId) {
                $transporterId = $this->transporterId;
                $query->where(function ($q) use ($transporterId) {
                    $q->where('transporter_id', $transporterId)
                        ->orWhereHas('freightBookings', function ($fb) use ($transporterId) {
                            $fb->where('transporter_id', $transporterId);
                        });
                });
            }

            $lrs = $query->get();
        } else {
            $lrs = [];
        }

        // When exporting, let the view know it's being rendered for Excel
        return view('reports.exports.dispatch', [
            'lrs' => $lrs,
            'forExcel' => true,
            'endDate' => $this->endDate,
            'balanceAsOnDate' => $this->balanceAsOnDate,
        ]);
    }

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
            'AI' => $numFormat,
            'AR' => NumberFormat::FORMAT_NUMBER,
            'AS' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
