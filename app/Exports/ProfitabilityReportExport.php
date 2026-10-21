<?php

namespace App\Exports;

use App\Models\LorryReceipt;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProfitabilityReportExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    private $companyId;
    private $startDate;
    private $endDate;
    private $customerId;
    private $transporterId;
    private $truckId;
    private $status;
    private $profitLoss;

    public function __construct(
        $companyId,
        $startDate,
        $endDate,
        $customerId = null,
        $transporterId = null,
        $truckId = null,
        $status = null,
        $profitLoss = null
    ) {
        $this->companyId = $companyId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->customerId = $customerId;
        $this->transporterId = $transporterId;
        $this->truckId = $truckId;
        $this->status = $status;
        $this->profitLoss = $profitLoss;
    }

    public function collection()
    {
        $query = LorryReceipt::query()
            ->with([
                'customer',
                'transporter.transporterMaster',
                'truck',
                'freightBookings',
            ])
            ->where('company_id', $this->companyId)
            ->whereBetween('gr_date', [
                $this->startDate,
                $this->endDate
            ])
            ->whereHas('freightBookings');

        // Customer filter
        if ($this->customerId) {
            $query->where('customer_id', $this->customerId);
        }

        // Transporter filter
        if ($this->transporterId) {
            $query->where('transporter_id', $this->transporterId);
        }

        // Vehicle filter
        if ($this->truckId) {
            $query->where('truck_id', $this->truckId);
        }

        // Status filter
        if ($this->status) {
            $query->where('delivery_status', $this->status);
        }

        $lrs = $query
            ->orderBy('gr_date', 'desc')
            ->get();

        $data = collect();

        /*
        | Summary data
        */

        $customerSummary = [];
        $transporterSummary = [];

        /*
        | Trip-wise data
        */

        foreach ($lrs as $lr) {

            $freightBooking = $lr->freightBookings->first();

            // Revenue
            $revenue = (float) (
                $lr->actual_amount
                ?? $lr->amount
                ?? 0
            );

            // Cost
            $cost = $freightBooking
                ? (float) $freightBooking->effectivePayableAmount()
                : 0;

            // Profit
            $profit = $revenue - $cost;

            // Margin
            $margin = $revenue > 0
                ? ($profit / $revenue) * 100
                : 0;

            /*
            | Profit / Loss filter
            */

            if ($this->profitLoss === 'profit' && $profit < 0) {
                continue;
            }

            if ($this->profitLoss === 'loss' && $profit >= 0) {
                continue;
            }

            /*
            | Customer name
            */

            $customerName = $lr->customer?->name ?? '-';

            /*
            | Transporter name
            */

            $transporterName =
                $lr->transporter?->transporterMaster?->name
                ?? $lr->transporter?->name
                ?? '-';

            /*
            | Trip-wise
            */

            $data->push([
                $lr->lr_number ?? '-',
                $lr->gr_date ?? '-',
                $customerName,
                $transporterName,
                $lr->truck?->truck_number ?? '-',
                round($revenue, 2),
                round($cost, 2),
                round($profit, 2),
                round($margin, 2),
                $lr->delivery_status ?? '-',
            ]);

            /*
            | Customer-wise summary
            */

            if (!isset($customerSummary[$customerName])) {
                $customerSummary[$customerName] = [
                    'trips' => 0,
                    'revenue' => 0,
                    'cost' => 0,
                    'profit' => 0,
                ];
            }

            $customerSummary[$customerName]['trips']++;
            $customerSummary[$customerName]['revenue'] += $revenue;
            $customerSummary[$customerName]['cost'] += $cost;
            $customerSummary[$customerName]['profit'] += $profit;

            /*
            | Transporter-wise summary
            */

            if (!isset($transporterSummary[$transporterName])) {
                $transporterSummary[$transporterName] = [
                    'trips' => 0,
                    'revenue' => 0,
                    'cost' => 0,
                    'profit' => 0,
                ];
            }

            $transporterSummary[$transporterName]['trips']++;
            $transporterSummary[$transporterName]['revenue'] += $revenue;
            $transporterSummary[$transporterName]['cost'] += $cost;
            $transporterSummary[$transporterName]['profit'] += $profit;
        }

        /*
        | Customer-wise section
        */

        $data->push([]);
        $data->push(['Customer-wise Profitability']);
        $data->push([
            'Customer',
            'Trips',
            'Revenue',
            'Cost',
            'Profit',
            'Margin %',
        ]);

        foreach ($customerSummary as $customer => $summary) {

            $margin = $summary['revenue'] > 0
                ? ($summary['profit'] / $summary['revenue']) * 100
                : 0;

            $data->push([
                $customer,
                $summary['trips'],
                round($summary['revenue'], 2),
                round($summary['cost'], 2),
                round($summary['profit'], 2),
                round($margin, 2),
            ]);
        }

        /*
        | Transporter-wise section
        */

        $data->push([]);
        $data->push(['Transporter-wise Profitability']);
        $data->push([
            'Transporter',
            'Trips',
            'Revenue',
            'Cost',
            'Profit',
            'Margin %',
        ]);

        foreach ($transporterSummary as $transporter => $summary) {

            $margin = $summary['revenue'] > 0
                ? ($summary['profit'] / $summary['revenue']) * 100
                : 0;

            $data->push([
                $transporter,
                $summary['trips'],
                round($summary['revenue'], 2),
                round($summary['cost'], 2),
                round($summary['profit'], 2),
                round($margin, 2),
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'LR / Trip',
            'Date',
            'Customer',
            'Transporter',
            'Vehicle',
            'Revenue',
            'Cost',
            'Profit',
            'Margin %',
            'Status',
        ];
    }
}