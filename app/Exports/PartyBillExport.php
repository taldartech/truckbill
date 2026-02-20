<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PartyBillExport implements FromView
{
  protected $id;

  public function __construct($id)
  {
    $this->id = $id;
  }

  /**
   * @return \Illuminate\Contracts\View\View
   */
  public function view(): View
  {
    $invoice = Invoice::find($this->id);
    $customDetails = collect();
    if ($invoice && $invoice->custom_invoice) {
      $invoice->load(['customInvoiceDetails.lorryReceipt.truck']);
      $customDetails = $invoice->customInvoiceDetails->sortBy('sort_order')->values();
    }
    return view('reports.exports.party-bill', compact('invoice', 'customDetails'));
  }
}
