<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromView;

class PartyLedgerExport implements FromView
{
    private $startDate, $endDate, $customerId;

    public function __construct($startDate, $endDate, $customerId)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->customerId = $customerId;
    }

    public function view() : View
    {
        $company_id = Auth::user()->company_id;

        $query = \App\Models\LorryReceipt::query()
            ->whereBetween('gr_date', [$this->startDate, $this->endDate])
            ->where('customer_id', $this->customerId)
            ->where('invoice_generated', 1);

        if ($company_id) {
            $query->where('company_id', $company_id);
        }

        $lrs = $query->get();
        $customer = \App\Models\Consignee::find($this->customerId);
        return view('reports.exports.party-ledger', compact('lrs', 'customer'));
    }
}
